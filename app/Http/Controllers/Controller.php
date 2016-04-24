<?php
namespace Jihe\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Jihe\Services\StorageService;
use Jihe\Http\Responses\RespondsJson;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class Controller extends BaseController
{
    use DispatchesJobs, RespondsJson;
    use ValidatesRequests {
        // build validation response on our own
        buildFailedValidationResponse as parentBuildFailedValidationResponse;
    }

    /**
     * override method defined in ValidatesRequests in order to make an
     * application specific validation error response
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        if ($request->ajax() || $request->wantsJson()) {
            // fetch first entry of the errors, which is derived from validation
            // , keyed by field name and valued by error messages (which is an array,
            // as a field can relate to multiple error messages)
            // we only need the first error message here
            $message = current(current($errors));

            return $this->jsonException($message);
        }

        return $this->parentBuildFailedValidationResponse($request, $errors);
    }

//    /**
//     * store an uploaded image to external storage as temp
//     *
//     * @param StorageService $storageService
//     * @param UploadedFile $uploaded
//     * @return array|string                     id of the file on external storage
//     */
//    protected final function storeUploadImageAsTemp(StorageService $storageService, $uploaded)
//    {
//        // UploadedFile implements __toString() method, and this is the only
//        // way to get its full path
//        $path = strval($uploaded);
//
//        $storageService->storeAsTmpImage($path, [
//            'ext' => (getimagesize($path)[2] == IMAGETYPE_PNG) ? 'png' : 'jpg',
//        ]);
//    }
//
//    /**
//     * store an uploaded file to external storage
//     *
//     * @param StorageService $storageService
//     * @param UploadedFile $uploaded | tmp image url
//     * @return array|string                     id of the file on external storage
//     */
//    protected final function storeUploadFile(StorageService $storageService, $uploaded)
//    {
//        // UploadedFile implements __toString() method, and this is the only
//        // way to get its full path
//        $path = strval($uploaded);
//
//        $storageService->storeAsFile($path, [
//            'ext'      => $uploaded instanceof UploadedFile ?
//                            $uploaded->getClientOriginalExtension() :
//                            (getimagesize($path)[2] == IMAGETYPE_PNG) ? 'png' : 'jpg',
//        ]);
//    }
//
//    /**
//     * store an uploaded image to external storage
//     *
//     * @param StorageService $storageService
//     * @param UploadedFile $uploaded | tmp image url
//     * @return array|string                     id of the file on external storage
//     */
//    protected final function storeUploadImage(StorageService $storageService, $uploaded)
//    {
//        $storageService->storeAsImage($uploaded);
//    }

    /**
     * sane page and size for paginated request
     *
     * @param int $page        page number
     * @param int $size        page size
     * @param array $options   - min_page      the min/starting page number(default to 1)
     *                         - max_size      max item per page
     *                         - default_size  default page size
     *
     * @return array               [0] is saned page#
     *                             [1] is saned page size
     */
    protected final function sanePageAndSize($page, $size, array $options = [])
    {
        if ($page <= 0) {
            $page = array_get($options, 'min_page', 1);
        }

        $maxSize = array_get($options, 'max_size', 100);
        if ($size <=0 || $maxSize <= 0) {
            $size = array_get($options, 'default_size', 15);
        } else if ($size > $maxSize) {
            $size = $maxSize;
        }

        return [$page, $size];
    }
}