<?php

namespace App\Http\Controllers\Admin;

use App\Conference;
use App\ConferenceAsset;
use App\Delegate;
use App\Document;
use App\Http\Controllers\Admin\Content\BaseController;
use App\Http\Requests\AssignDocumentRequest;
use App\Http\Requests\EditDocumentRequest;
use App\Media;
use Illuminate\Http\Request;

class DocumentController extends BaseController
{

    public function index() {
        $docs_raw = ConferenceAsset::where('conference_id', $this->getActiveConferenceId())->get();
        $delegates_raw = Delegate::where('conference_id', $this->getActiveConferenceId())->get();
        $assigned_docs_raw = Document::where('conference_id', $this->getActiveConferenceId())->with('asset', 'delegate')->get();
        $delegates = [];
        $docs = [];
        $assigned_docs = [];

        if ($delegates_raw->isNotEmpty()) {
            foreach ($delegates_raw as $delegate) {
                $delegates[] = ['text' => $delegate->first_name . ' ' . $delegate->last_name, 'id' => $delegate->id];
            }
        }

        if ($docs_raw->isNotEmpty()) {
            foreach ($docs_raw as $doc) {
                $docs[] = ['text' => $doc->filename, 'id' => $doc->id];
            }
        }

        if ($assigned_docs_raw->isNotEmpty()) {
            foreach ($assigned_docs_raw as $assigned) {
                if ($assigned->asset != null && $assigned->delegate != null)
                    $assigned_docs[] = [
                        'category' => $assigned->category,
                        'id'       => $assigned->id,
                        'delegate' => $assigned->delegate->first_name . ' ' . $assigned->delegate->last_name,
                        'asset'    => $assigned->asset->filename
                    ];
            }
        }

        $delegates = json_encode($delegates);
        $docs = json_encode($docs);
        $assigned_docs = json_encode($assigned_docs);

        return view('admin.document.index', compact('docs', 'delegates', 'assigned_docs'));

    }

    public function get(Request $r, $id) {
        $doc = Document::where('id', $id)->first();
        return $doc;
    }

    public function createPost(AssignDocumentRequest $r) {
        $doc = new Document();

        $doc->asset_id = $r->input('asset_id');
        $doc->delegate_id = $r->input('delegate_id');
        $doc->category = $r->input('category');
        $doc->conference_id = $this->getActiveConferenceId();

        $doc->save();

        return response()->json(
            [
                'success' => true,
            ]
        );
    }

    public function edit(EditDocumentRequest $r, $id) {
        $doc = Document::where('id', $id)->first();
        if (!$doc) {
            return response()->json(['error' => true]);
        }
        $doc->asset_id      = $r->input('asset_id');
        $doc->delegate_id   = $r->input('delegate_id');
        $doc->category      = $r->input('category');
        $doc->conference_id = $this->getActiveConferenceId();

        $doc->save();

        return response()->json(
            [
                'success' => true,
            ]
        );
    }

}
