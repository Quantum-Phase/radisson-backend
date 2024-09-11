<?php

namespace App\Http\Controllers;

use App\Models\Block;
use Illuminate\Http\Request;


class BlockController extends Controller
{
    public function showBlock()
    {
        $showBlock = Block::all();
        return response()->json($showBlock);
    }
}
