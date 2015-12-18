<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use Github\Exception\RuntimeException;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;

class ReplyController extends Controller
{
    public function reply($login, $repo, $issue)
    {
        $data = ['success' => 0];

        if (Request::isMethod('post')) {
            $body = trim(Input::get('reply'));
            if ($body) {
                try {
                    GitHub::issue()->comments()->create($login, $repo, $issue, ['body' => $body]);

                    $data['success'] = 1;
                } catch (RuntimeException $exception) {
                    $data['error'] = $exception->getMessage();
                }
            }
        }

        $response = Response::make(json_encode($data), 200);

        $response->header('Content-Type', 'application/json');

        return $response;
    }
}