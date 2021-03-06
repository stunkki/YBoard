<?php
namespace YBoard\Traits;

trait ErrorPages
{
    protected function badRequest($errorTitle = false, $errorMessage = false)
    {
        $errorTitle = empty($errorTitle) ? _('Bad request') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('Your request did not complete because it contained invalid information.') : $errorMessage;
        $this->dieWithMessage($errorTitle, $errorMessage, 400);
    }

    protected function unauthorized($errorTitle = false, $errorMessage = false)
    {
        $errorTitle = empty($errorTitle) ? _('Unauthorized') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('You are not authorized to perform this operation') : $errorMessage;
        $this->dieWithMessage($errorTitle, $errorMessage, 401);
    }

    protected function blockAccess($errorTitle = false, $errorMessage = false)
    {
        $errorTitle = empty($errorTitle) ? _('Forbidden') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('Thou shalt not access this resource!') : $errorMessage;
        $this->dieWithMessage($errorTitle, $errorMessage, 403);
    }

    public function notFound($errorTitle = false, $errorMessage = false)
    {
        $errorTitle = empty($errorTitle) ? _('Page not found') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('Whatever you were looking for does not exist here. Probably never did.') : $errorMessage;

        $images = glob(ROOT_PATH . '/public/static/img/404/*.*');
        if(!empty($images)) {
            $image = $this->imagePathToUrl($images[array_rand($images)]);
        } else {
            $images = false;
        }

        $this->dieWithMessage($errorTitle, $errorMessage, 404, 'notfound', $image);
    }

    protected function internalError($errorTitle = false, $errorMessage = false)
    {
        $errorTitle = empty($errorTitle) ? _('Oh noes!') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('We\'re terribly sorry. An internal error occurred when we tried to complete your request.') : $errorMessage;

        $images = glob(ROOT_PATH . '/public/static/img/500/*.*');
        if(!empty($images)) {
            $image = $this->imagePathToUrl($images[array_rand($images)]);
        } else {
            $images = false;
        }

        $this->dieWithMessage($errorTitle, $errorMessage, 500, 'internalerror', $image);
    }

    protected function imagePathToUrl($path)
    {
        return $this->config['view']['staticUrl'] . str_replace(ROOT_PATH . '/static', '', $path);
    }

    abstract protected function dieWithMessage(
        $errorTitle,
        $errorMessage,
        $httpStatus = false,
        $bodyClass = false,
        $image = false
    );
}
