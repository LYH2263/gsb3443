<?php

namespace app\controller;

use app\model\BrowseHistory;
use app\model\Album;
use think\facade\Db;
use think\Request;

class BrowseHistoryController
{
    public function record(Request $request)
    {
        $albumId = $request->post('album_id', 0);
        if (empty($albumId) || !is_numeric($albumId)) {
            return json_error('画册ID不能为空');
        }

        $album = Album::find($albumId);
        if (!$album || $album->status !== 1) {
            return json_error('画册不存在或未发布', 404);
        }

        $existing = BrowseHistory::where('user_id', $request->uid)
            ->where('album_id', $albumId)
            ->find();

        if ($existing) {
            $existing->updated_at = date('Y-m-d H:i:s');
            $existing->save();
        } else {
            BrowseHistory::create([
                'user_id' => $request->uid,
                'album_id' => $albumId,
            ]);
        }

        return json_success([], '记录成功');
    }

    public function list(Request $request)
    {
        $list = BrowseHistory::alias('bh')
            ->join('albums a', 'bh.album_id = a.id')
            ->where('bh.user_id', $request->uid)
            ->where('a.status', 1)
            ->order('bh.updated_at', 'desc')
            ->limit(10)
            ->field('bh.album_id, bh.updated_at as browsed_at, a.title, a.cover_image')
            ->select()
            ->each(function ($item) {
                $item->cover_image_url = $item->cover_image ? get_upload_url($item->cover_image) : '';
                unset($item->cover_image);
            });

        return json_success($list);
    }
}
