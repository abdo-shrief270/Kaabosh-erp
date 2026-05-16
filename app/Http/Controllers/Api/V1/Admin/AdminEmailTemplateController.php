<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Communication\Models\EmailTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminEmailTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = EmailTemplate::orderBy('key')->get();

        return response()->json(['data' => $templates]);
    }

    public function show(EmailTemplate $emailTemplate): JsonResponse
    {
        return response()->json(['data' => $emailTemplate]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => 'required|string|max:100|unique:email_templates,key',
            'name' => 'required|string|max:255',
            'subject_ar' => 'required|string|max:255',
            'subject_en' => 'required|string|max:255',
            'body_ar' => 'required|string',
            'body_en' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $template = EmailTemplate::create($data);

        return response()->json(['data' => $template], Response::HTTP_CREATED);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'subject_ar' => 'nullable|string|max:255',
            'subject_en' => 'nullable|string|max:255',
            'body_ar' => 'nullable|string',
            'body_en' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $emailTemplate->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json(['data' => $emailTemplate->fresh()]);
    }

    /**
     * Preview a rendered template with sample data.
     */
    public function preview(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $locale = $request->input('locale', 'ar');

        // Build sample data from template variables
        $sampleData = [];
        foreach ($emailTemplate->variables ?? [] as $var) {
            $sampleData[$var] = "[{$var}]";
        }

        return response()->json([
            'subject' => $emailTemplate->renderSubject($locale, $sampleData),
            'body' => $emailTemplate->renderBody($locale, $sampleData),
        ]);
    }
}
