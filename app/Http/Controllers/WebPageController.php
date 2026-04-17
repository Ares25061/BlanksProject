<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebPageController extends Controller
{
    public function profile(Request $request)
    {
        return view('profile', ['user' => $request->id ?? $request->user()]);
    }

    public function profileEdit(Request $request)
    {
        return view('profile-edit', ['user' => $request->id ?? $request->user()]);
    }

    public function testsShow($id)
    {
        return view('tests.show', ['id' => $id]);
    }

    public function testsEdit($id)
    {
        return view('tests.edit', ['id' => $id]);
    }

    public function groupsShow($id)
    {
        return view('groups.show', ['id' => $id]);
    }
}
