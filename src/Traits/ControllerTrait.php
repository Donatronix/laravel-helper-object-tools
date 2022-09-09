<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

trait ControllerTrait
{
    use AlertMessages;
    use FlashMessages;
    use HTMLMessages;
    use ToastMessages;

    /**
     * @var null
     */
    protected mixed $data;

    /**
     * @param $title
     * @param $subTitle
     */
    public function setPageTitle($title, $subTitle = null)
    {
        view()->share(['pageTitle' => $title, 'subTitle' => $subTitle]);
    }

    /**
     * @param  array  $value
     */
    public function setPageValue(array $value): void
    {
        view()->share($value);
    }

    /**
     * @param  null  $message
     */
    public function showErrorPage(int $errorCode = 404, $message = null): Response
    {
        $data['message'] = $message;

        return response()->view('errors.'.$errorCode, $data, $errorCode);
    }

    /**
     * @param  array  $message
     * @param  null  $data
     */
    public function responseJson(bool $error = true, int $responseCode = 200, array $message = [], $data = null): JsonResponse
    {
        return response()->json([
            'error' => $error,
            'response_code' => $responseCode,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * @param    $route
     * @param  string  $message
     * @param  string  $type
     * @param  bool  $error
     * @param  bool  $withOldInputWhenError
     * @return RedirectResponse
     */
    public function responseRedirect($route, string $message, string $type = 'info', bool $error = false, bool $withOldInputWhenError = false): RedirectResponse
    {
        $this->setFlashMessage($message, $type);
        $this->showFlashMessages();

        if ($error && $withOldInputWhenError) {
            return redirect()->back()->withInput();
        }

        return redirect($route);
    }

    public function responseRedirectBack(string $message, string $type = 'info', bool $error = false, bool $withOldInputWhenError = false): RedirectResponse
    {
        $this->setFlashMessage($message, $type);
        $this->showFlashMessages();
        if ($error && $withOldInputWhenError) {
            return redirect()->back()->withInput();
        }

        return redirect()->back();
    }
}
