<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebContentController extends Controller
{

    function __construct()
    {
        $this->middleware('permission:User View', ['only' => ['listUsers']]);
        $this->middleware('permission:User Add', ['only' => ['register']]);
        $this->middleware('permission:User Edit', ['only' => ['editUser','updateUser']]);
    }

    /* Home tab data fetch part GET */
    public function getWebHomeContent($lang)
    {
        try {
            $tranlateArray = array();
            $webContent = WebContent::where('slug','home')->first();

            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }

            $webTranslations = WebContainTranslation::where('web_content_id', $webContent->id)
                ->where('language', $lang)
                ->first();

            if (!empty($webTranslations)) {
                // Decode the JSON translation data
                $tranlateArray = json_decode($webTranslations->translated_value, true);
            }else{
                // For Defualt Language Data Fetch
                $defaultData = WebContainTranslation::where('web_content_id', $webContent->id)
                    ->where('language', 'en')
                    ->first();
                    
                if (!empty($defaultData)) {
                    // Decode the JSON translation data
                    $tranlateArray = json_decode($defaultData->translated_value, true);
                }    
            }

            $tranlateArray['header_image'] = $webContent->header_image ? $this->getImageUrl($webContent->header_image) : null;
            $tranlateArray['sec_two_image'] = $webContent->sec_two_image ? $this->getImageUrl($webContent->sec_two_image) : null;
            $tranlateArray['sec_four_image'] = $webContent->sec_four_image ? $this->getImageUrl($webContent->sec_four_image) : null;


            // Process client_section images
            if (isset($tranlateArray['client_section'])) {
                foreach ($tranlateArray['client_section'] as $index => $section) {
                    $tranlateArray['client_section'][$index]['old_image'] = $section['image'] ? $section['image'] : null;
                    $tranlateArray['client_section'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['client_section'] = [];
            }

            return response()->json([
                'status' => 'true',
                'data' => $tranlateArray
            ], Response::HTTP_OK);

            // return $translationContent;
            
        } catch (\Exception $ex) {
            
            Log::error('error_webcontent_get_function',$ex->getMessage());
            return response()->json(['status' => 'false', 'message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* Home tab data insertion part POST */
    public function createOrUpdateWebHome(WebHomeInsertRequest $request,$lang)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules());
            $webContent = WebContent::where('slug','home')->first();
            $imgPaths = [];

            if($validator->fails()){
                return response()->json([
                    'status' => 403,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if(!$webContent){
                $webContent = new WebContent();
                $webContent->slug = 'home';
            }


            foreach(['header_image','sec_two_image','sec_four_image'] as $imgField){
                if($request->hasFile($imgField)){
                    if ($webContent->$imgField) {
                        Storage::disk('public')->delete($webContent->$imgField);
                    }

                    $imagePath = $request->file($imgField)->store('web_content_images', 'public');
                    $webContent->$imgField = $imagePath;
                }
            }

            $webContent->save();

            $originalText = [
                'meta_tag' => $request->meta_tag,
                'meta_description'=>$request->meta_description,
                'schema_code'=> $request->schema_code,
                'top_right_content'=>$request->top_right_content,
                'header_one'=>$request->header_one,
                'header_two'=>$request->header_two,
                'sec_two_header_one'=>$request->sec_two_header_one,
                'sec_two_header_two'=>$request->sec_two_header_two,
                'sec_two_paragraph'=>$request->sec_two_paragraph,
                'sec_two_name'=>$request->sec_two_name,
                'sec_two_details'=>$request->sec_two_details,
                'sec_three_header_one'=>$request->sec_three_header_one,
                'sec_three_header_two'=>$request->sec_three_header_two,
                'sec_three_paragraph'=>$request->sec_three_paragraph,
                'sec_four_header_one'=>$request->sec_four_header_one,
                'sec_four_header_two'=>$request->sec_four_header_two,
                'sec_four_paragraph'=>$request->sec_four_paragraph,
                'sec_four_fact_one' => $request->sec_four_fact_one,
                'sec_four_fact_one_title'=>$request->sec_four_fact_one_title,
                'sec_four_fact_two' => $request->sec_four_fact_two,
                'sec_four_fact_two_title'=>$request->sec_four_fact_two_title,
                'sec_four_fact_three' => $request->sec_four_fact_three,
                'sec_four_fact_three_title'=>$request->sec_four_fact_three_title,
                'client_section_title' => $request->client_section_title,
            ];

            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);
            
            // Process client_section images
            if (isset($translation['client_section'])) {
                foreach ($translation['client_section'] as $index => $section) {
                    $imageKey = "translation.client_section.$index.image";
                    if ($request->hasFile($imageKey)) {
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'client_section');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $imageFile = $request->file($imageKey);
                        $imagePath = $imageFile->store('web_content_images', 'public');
                        $translation['client_section'][$index]['image'] = $imagePath;
                    } else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'client_section');
                        $translation['client_section'][$index]['image'] = $oldImagePath;
                    }
                }
            }

            $array_merge = array_merge($originalText,$translation);
          
            WebContainTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContent->id],
                ['translated_value' => json_encode($array_merge, JSON_UNESCAPED_UNICODE)]
            );

            return response()->json(['status' => 'true', 'message' => 'Web content saved or updated successfully'], 200);

        } catch (\Exception $ex) {
            Log::error('error_webcontent_store_function',$ex->getMessage());
            return response()->json(['status' => 'false', 'message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            // return $ex;
        }
    }
}
