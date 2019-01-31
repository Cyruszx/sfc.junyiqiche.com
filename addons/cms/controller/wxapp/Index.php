<?php

namespace addons\cms\controller\wxapp;

use addons\cms\model\Archives;
use addons\cms\model\Block;
use addons\cms\model\Channel;
use addons\cms\model\RideSharing;
use app\common\model\Addon;

/**
 * 首页
 */
class Index extends Base
{

    protected $noNeedLogin = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 首页
     */
    public function index()
    {
        $bannerList = [];
        $list = Block::getBlockList(['name' => 'focus', 'row' => 5]);
        foreach ($list as $index => $item) {
            $bannerList[] = ['image' => cdnurl($item['image'], true), 'url' => '/', 'title' => $item['title']];
        }

        $tabList = [
            ['id' => 0, 'title' => '全部'],
        ];
        $channelList = Channel::where('status', 'normal')
            ->where('type', 'in', ['list'])
            ->field('id,parent_id,name,diyname')
            ->order('weigh desc,id desc')
            ->cache(false)
            ->select();
        foreach ($channelList as $index => $item) {
            $tabList[] = ['id' => $item['id'], 'title' => $item['name']];
        }
        $archivesList = Archives::getArchivesList([]);
        $data = [
            'bannerList'   => $bannerList,
            'tabList'      => $tabList,
            'archivesList' => $archivesList,
        ];
        $this->success('', $data);

    }

    /**
     *司机发布顺风车接口
     */
    public function submit_tailwind()
    {
        $arr = [
            'phone' => '18683787363',
            'starting_time' => '2019-02-19 10:56:09',
            'starting_point' => '火车北站',
            'destination' => '万年场',
            'money' => '70',
            'number_people' => 2,
            'note' => '马上开了',
            'type'=>'driver'
        ];

        $user_id = $this->request->post('user_id');

        $info = $this->request->post('info/a');

//        $this->success($info);

        if (!$user_id || !$info) {
            $this->error('缺少参数，请求失败', 'error');
        }
//        $info = "{\"phone\":\"18683787363\",\"starting_time\":\"2019-02-19 10:56:09\",\"starting_point\":\"\\u706b\\u8f66\\u5317\\u7ad9\",\"destination\":\"\\u4e07\\u5e74\\u573a\",\"money\":\"70\",\"number_people\":2,\"note\":\"\\u9a6c\\u4e0a\\u5f00\\u4e86\",\"type\":\"passenger\"}";

//        $this->success(json_encode($arr));
//        $info = json_decode($arr, true);

        $info['user_id'] = $user_id;
        RideSharing::create($info) ? $this->success('发布成功', 'success') : $this->error('发布失败', 'error');

    }

    /**
     * 顺风车列表接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function downwind()
    {
        $time = time();
        $type = $this->request->post('type');

        if (!$type) {
            $this->error('缺少参数，请求失败', 'error');
        }

        $field = $type == 'driver' ? ',money' : null;

        $takeCarList = RideSharing::field('id,starting_point,destination,starting_time,number_people,note,phone' . $field)
            ->order('createtime desc')->where('type', $type)->select();
        $overdueId = [];

        $takeCar = [];

        foreach ($takeCarList as $k => $v) {
            if ($time > strtotime($v['starting_time'])) {
                $overdueId[] = $v['id'];
            } else {
                $takeCar[] = $v;
            }
        }

        if ($overdueId) {
            RideSharing::where('id', 'in', $overdueId)->update(['status' => 'hidden']);
        }

        $this->success('请求成功', ['takeCarList' => $takeCar]);
    }


}
