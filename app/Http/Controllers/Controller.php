<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Base application controller.
 *
 * Provides:
 * - $this->authorize(...) via AuthorizesRequests
 * - $this->validate(...) via ValidatesRequests
 * - $this->middleware(...) via BaseController
 *
 * @method void middleware($middleware, array $options = [])
 * @method \Illuminate\Auth\Access\Response authorize(string $ability, mixed $arguments = [])
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
