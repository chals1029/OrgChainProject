<?php

namespace App\VotingSystem\Controllers;

use App\VotingSystem\Core\Controller;
use App\VotingSystem\Models\Election;

class HomeController extends Controller
{
    public function index(): void
    {
        $election = (new Election())->current();

        $this->view('home/index', [
            'title' => 'OrgChain Official Voting',
            'election' => $election,
        ]);
    }
}
