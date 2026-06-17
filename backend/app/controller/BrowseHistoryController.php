<?php

namespace app\controller;

use app\model\BrowseHistory;
use app\model\Album;
use think\facade\Log;
use think\Request;

class BrowseHistoryController
{
    public function record(Request $request, $albumId)
    {
        $albumId = (int)$albumId;
        if ($albumId <= 0) {
            return json_error('画册ID无效');
        }

        $album = Album::find($albumId);
        if (!$album) {
            return json_error('画册不存在', 404);
        }

        $userId = $request->uid;

        $existing = BrowseHistory::where('user_id', $userId)
            ->where('album_id', $albumId)
            ->find();

        if ($existing) {
            $existing->created_at = date('Y-m-d H:i:s');
            $existing->save();
        } else {
            BrowseHistory::create([
                'user_id'    => $userId,
                'album_id'   => $albumId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        Log::info("用户浏览画册: user_id={$userId}, album_id={$albumId}");

        return json_success([], '记录成功');
    }

    public function index(Request $request)
    {
        $userId = $request->uid;
        $limit = 10;

        // 用 JOIN 取已发布(status=1)画册的浏览记录，按时间倒序；
        // 避免 whereHas 在当前 ORM 版本上的 "where express error" 问题。
        $rows = BrowseHistory::alias('bh')
            ->join('albums a', 'a.id = bh.album_id')
            ->where('bh.user_id', $userId)
            ->where('a.status', 1)
            ->order('bh.created_at', 'desc')
            ->field('bh.id, bh.album_id, bh.created_at, a.title, a.cover_image')
            ->limit($limit * 2)
            ->select();

        $seenAlbums = [];
        $result = [];
        foreach ($rows as $row) {
            if (isset($seenAlbums[$row['album_id']])) {
                continue;
            }
            $seenAlbums[$row['album_id']] = true;

            $result[] = [
                'id'              => (int) $row['id'],
                'album_id'        => (int) $row['album_id'],
                'album_title'     => $row['title'],
                'cover_image_url' => $row['cover_image'] ? get_upload_url($row['cover_image']) : '',
                'browse_time'     => $row['created_at'],
            ];

            if (count($result) >= $limit) {
                break;
            }
        }

        return json_success([
            'list'  => $result,
            'total' => count($result),
        ]);
    }
}
