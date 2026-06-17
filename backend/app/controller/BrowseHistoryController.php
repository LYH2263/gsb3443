<?php

namespace app\controller;

use app\model\BrowseHistory;
use app\model\Album;
use think\Request;

class BrowseHistoryController
{
    public function record(Request $request)
    {
        $albumId = $request->post('album_id', 0);

        if (empty($albumId)) {
            return json_error('画册ID不能为空');
        }

        $album = Album::find($albumId);
        if (!$album || $album->status !== 1) {
            return json_error('画册不存在或未发布');
        }

        $userId = $request->uid;

        $existing = BrowseHistory::where('user_id', $userId)
            ->where('album_id', $albumId)
            ->find();

        if ($existing) {
            $existing->browse_at = date('Y-m-d H:i:s');
            $existing->save();
        } else {
            BrowseHistory::create([
                'user_id'  => $userId,
                'album_id' => $albumId,
                'browse_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return json_success([], '记录成功');
    }

    public function list(Request $request)
    {
        $userId = $request->uid;

        // 通过 JOIN 仅取已发布(status=1)画册的浏览记录，
        // 同画册已在写入层用唯一键去重，这里按浏览时间倒序取最多 10 条。
        $rows = BrowseHistory::alias('bh')
            ->join('albums a', 'a.id = bh.album_id')
            ->where('bh.user_id', $userId)
            ->where('a.status', 1)
            ->order('bh.browse_at', 'desc')
            ->limit(10)
            ->field('bh.id, bh.album_id, bh.browse_at, a.title, a.cover_image')
            ->select();

        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'id'        => (int) $row['id'],
                'album_id'  => (int) $row['album_id'],
                'browse_at' => $row['browse_at'],
                'album'     => [
                    'id'              => (int) $row['album_id'],
                    'title'           => $row['title'],
                    'cover_image_url' => $row['cover_image'] ? get_upload_url($row['cover_image']) : '',
                ],
            ];
        }

        return json_success([
            'list'  => $list,
            'total' => count($list),
        ]);
    }
}
