<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\TokenRepositoryInterface;

class TokenController extends Controller
{
    public function __construct(
        private TokenRepositoryInterface $tokens,
    ) {}

    public function index()
    {
        return view('admin.tokens.index', [
            'tokens' => $this->tokens->getAll(),
        ]);
    }

    public function destroy(int $id)
    {
        $token = $this->tokens->find($id);
        $this->tokens->delete($token);

        return back()->with('success', 'Token revoked.');
    }
}