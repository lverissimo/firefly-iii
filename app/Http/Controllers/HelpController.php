<?php
/**
 * HelpController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Helpers\Help\HelpInterface;
use Illuminate\Http\JsonResponse;
use Log;

/**
 * Class HelpController.
 */
class HelpController extends Controller
{
    /** @var HelpInterface Help interface. */
    private $help;

    /**
     * HelpController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->help = app(HelpInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show help for a route.
     *
     * @param   $route
     *
     * @return JsonResponse
     */
    public function show(string $route): JsonResponse
    {
        /** @var string $language */
        $language = app('preferences')->get('language', config('firefly.default_language', 'en_US'))->data;
        $html     = $this->getHelpText($route, $language);

        return response()->json(['html' => $html]);
    }

    /**
     * Gets the help text.
     *
     * @param string $route
     * @param string $language
     *
     * @return string
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getHelpText(string $route, string $language): string
    {
        // get language and default variables.
        $content = '<p>' . trans('firefly.route_has_no_help') . '</p>';

        // if no such route, log error and return default text.
        if (!$this->help->hasRoute($route)) {
            Log::error('No such route: ' . $route);

            return $content;
        }

        // help content may be cached:
        if ($this->help->inCache($route, $language)) {
            $content = $this->help->getFromCache($route, $language);
            Log::debug(sprintf('Help text %s was in cache.', $language));

            return $content;
        }

        // get help content from Github:
        $content = $this->help->getFromGitHub($route, $language);

        // content will have 0 length when Github failed. Try en_US when it does:
        if ('' === $content) {
            $language = 'en_US';

            // also check cache first:
            if ($this->help->inCache($route, $language)) {
                Log::debug(sprintf('Help text %s was in cache.', $language));
                $content = $this->help->getFromCache($route, $language);

                return $content;
            }

            $content = $this->help->getFromGitHub($route, $language);
        }

        // help still empty?
        if ('' !== $content) {
            $this->help->putInCache($route, $language, $content);

            return $content;
        }

        return '<p>' . trans('firefly.route_has_no_help') . '</p>';
    }
}
