<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\IdentificationType;
use App\Http\Requests\DownloadInformedConsentRequest;
use App\Models\CompanyDetail;
use App\Models\Contact;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

final class DownloadInformedConsentController
{
    public function __invoke(DownloadInformedConsentRequest $request, Contact $contact): Response
    {
        if (! defined('DOMPDF_ENABLE_REMOTE')) {
            define('DOMPDF_ENABLE_REMOTE', false);
        }

        $prescription = $request->filled('prescription_id')
            ? Prescription::query()->where('patient_id', $contact->id)->findOrFail($request->integer('prescription_id'))
            : null;

        $isRepresentative = $request->input('signer') === 'representative';

        $documentType = $isRepresentative
            ? $request->input('representative_document_type')
            : $this->patientDocumentType($contact);

        $documentNumber = $isRepresentative
            ? $request->input('representative_document_number')
            : $contact->identification_number;

        $pdf = Pdf::loadView('contacts.informed-consent', [
            'company' => CompanyDetail::getAll(),
            'patientName' => $contact->name,
            'isRepresentative' => $isRepresentative,
            'representativeName' => $isRepresentative ? $request->input('representative_name') : null,
            'attentionDate' => ($prescription?->created_at ?? now())->copy(),
            'documentType' => $documentType,
            'documentNumber' => $documentNumber,
            'clinicalRecordNumber' => mb_str_pad((string) $contact->id, 5, '0', STR_PAD_LEFT),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('consentimiento-informado-'.Str::slug($contact->name).'.pdf');
    }

    private function patientDocumentType(Contact $contact): ?string
    {
        return match ($contact->identification_type) {
            IdentificationType::Cedula->value => 'cedula',
            IdentificationType::Pasaporte->value => 'pasaporte',
            default => null,
        };
    }
}
