<?php

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class niconicoApiController extends Controller
{
    /**
     * ランダムでボーカロイドの音楽を取得する
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function index(Request $request) {
        try {
            // TODO: add validation
            $vocal = '';
            $genre = '';
            $offset = 0;
            $limit = 0;
            // 使用するニコニコ動画API
            $baseUrl = 'http://api.search.nicovideo.jp/api/v2/video/contents/search?q=';
            // 検索から排除する条件
            $eliminationCriteria = urlencode(' -StepMania -歌ってみた -演奏して見た -MMD -MMD杯 -mikumikudance -クロスフェード -VOCALOIDランキング -作業用BGM -クロスフェードデモ -ボカロカラオケDB -VOCALOIDにインタビューシリーズ');
            // 選択されたVOCALとジャンルを整形
            if ($request->has('vocal')) {
                foreach ($request->vocal as $key => $value) {
                    if ($key == 0) {
                        $vocal .= $value;
                        continue;
                    }
                    $vocal .= ' OR ' . $value;
                }
            } else {
                $vocal = '初音ミク';
            }
            if ($request->has('genre')) {
                foreach ($request->genre as $genreKey => $genreValue) {
                    if ($genreKey == 0) {
                        $vocal .= ' ' .$genreValue;
                        break;
                    }
                }
            }
            $searchCriteria = urlencode($vocal . $genre) . $eliminationCriteria;
            // 検索結果の動画数を取得
            $client = new Client();
            $request = $client->get($baseUrl . $searchCriteria . $this->getURLOptions($offset, $limit));
            $response = json_decode($request->getBody(), true);
            $videoCountRange = (int) $response['meta']['totalCount'];
            if (1600 < $videoCountRange) {
                $maxRange = 1600;
            } else {
                $maxRange = $videoCountRange;
            }
            $offset = mt_rand(0, ($maxRange));
            $limit = ($videoCountRange > 100) ? 100 : $videoCountRange;
            // ランダムに動画を取得
            $request = $client->get($baseUrl . $searchCriteria . $this->getURLOptions($offset, $limit));
            $response = json_decode($request->getBody(), true);
            return $response;
        } catch (Exception $exception) {
            Log::error($exception);
            return response()->json('', 400);
        }

    }

    /**
     * APIのオプションを返す
     * @param $offset
     * @param $limit
     * @return string
     */
    private function getURLOptions($offset, $limit) {
        return '&targets=tagsExact&fields=contentId,title,viewCounter,startTime&filters[viewCounter][gte]=10000&filters[categoryTags][0]=VOCALOID&_sort=%2bviewCounter&_offset=' . $offset . '&_limit='  . $limit . '&_context=apiguide';
    }
}
