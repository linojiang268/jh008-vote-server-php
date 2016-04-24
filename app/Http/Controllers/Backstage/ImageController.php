<?php
namespace Jihe\Http\Controllers\Backstage;

use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\StorageService;

class ImageController extends Controller
{
    /**
     * store image file as tmp file
     */
    public function tmpUpload(Request $request, StorageService $storageService)
    {
        $this->validate($request, [
            //'image' => 'required|mimes:gif,jpeg,bmp,png,jpg',
            'image' => 'required|mimes:jpeg,png,jpg',
        ], [
            'image.required' => 'image未设置',
            'image.mimes' => 'image错误',
        ]);

        /* @var $image \Symfony\Component\HttpFoundation\File\UploadedFile */
        $image = $request->file('image');

        try {
            // store the image file
            $imageUrl = $storageService->storeAsTmpImage($image);

            @unlink($image);
            return $this->json(['image_url' => $imageUrl]);
        } catch (\Exception $ex) {
            @unlink($image);
            return $this->jsonException($ex);
        }
    }
    
    /**
     * store image file from ueditor
     */
    public function uploadFromUeditor(Request $request, StorageService $storageService)
    {
        $this->validate($request, [
            //'file' => 'required|mimes:gif,jpeg,bmp,png,jpg',
            'file' => 'required|mimes:jpeg,png,jpg',
        ], [
            'file.required' => '上传文件为空',
            'file.mimes'    => '不允许的文件类型',
        ]);

        /* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
        $file = $request->file('file');
        try {
            // store the image file with tmpimage
            $fileUrl = $storageService->storeAsTmpImage($file);

            @unlink($file);
            return response()->json($this->getFileInfo(
                                                       'SUCCESS', 
                                                       $fileUrl, 
                                                       $fileUrl, 
                                                       $file->getClientOriginalName(), 
                                                       $file->getClientOriginalExtension(), 
                                                       $file->getClientSize()));
        } catch (\Exception $ex) {
            @unlink($file);
            return response()->json($this->getFileInfo($ex->getMessage()));
        }
    }
    
    private function getFileInfo($state, 
                                 $url = null, 
                                 $title = null, 
                                 $original = null, 
                                 $type = null, 
                                 $size = null) 
    {
        return [
                'state'    => $state,
                'url'      => $url,
                'title'    => $title,
                'original' => $original,
                'type'     => $type,
                'size'     =>$size,
        ];
    }
}