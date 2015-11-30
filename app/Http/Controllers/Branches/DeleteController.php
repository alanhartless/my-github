<?php

namespace App\Http\Controllers\Branches;

use App\Http\Controllers\Controller;
use Github\Exception\RuntimeException;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Support\Facades\Response;

class DeleteController extends Controller
{
    public function deleteBranch($login, $repo, $branch)
    {
        $data = ['success' => 0];

        try {
            GitHub::gitData()->references()->remove($login, $repo, 'heads/'.$branch);

            $data['success'] = 1;
        } catch (RuntimeException $exception) {
            $data['error'] = $exception->getMessage();
        }

        $response = Response::make(json_encode($data), 200);

        $response->header('Content-Type', 'application/json');

        return $response;
    }
}