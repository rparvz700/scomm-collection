<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Waiver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WaiverController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isTeamScoped = $user && !$user->can('view all clients') && !$user->hasAnyRole(['admin', 'mgt', 'collection_hod', 'manager', 'Super Admin']);

        $query = Waiver::query()->with('client')->latest('applicable_from_date');

        if ($isTeamScoped) {
            $userId = $user->id;
            $userName = strtolower(trim($user->name));
            $userEmail = $user->email;

            $query->where(function ($q) use ($userId, $userName, $userEmail) {
                $q->where('created_by', $userName)
                  ->orWhere('created_by', $userEmail)
                  ->orWhereHas('client', function ($cq) use ($userId, $userName) {
                      $cq->where('collection_kam_id', $userId)
                        ->orWhereRaw('LOWER(collection_kam) = ?', [$userName])
                        ->orWhere('sm_kam_id', $userId)
                        ->orWhereRaw('LOWER(sm_kam) = ?', [$userName])
                        ->orWhere('nttn_billing_kam_id', $userId)
                        ->orWhereRaw('LOWER(nttn_billing_kam) = ?', [$userName])
                        ->orWhere('iig_itc_billing_kam_id', $userId)
                        ->orWhereRaw('LOWER(iig_itc_billing_kam) = ?', [$userName])
                        ->orWhere('collection_supervisor_id', $userId)
                        ->orWhereRaw('LOWER(collection_supervisor) = ?', [$userName])
                        ->orWhere('sm_supervisor_id', $userId)
                        ->orWhereRaw('LOWER(sm_supervisor) = ?', [$userName]);
                  });
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('applicable_from_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('applicable_from_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('waiver_amount', 'like', "%{$search}%")
                  ->orWhere('created_by', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('client_name', 'like', "%{$search}%")
                         ->orWhere('opus_id', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 25);
        $waivers = $query->paginate($perPage)->withQueryString();

        $clientsQuery = Client::query()->orderBy('client_name');
        if ($isTeamScoped) {
            $userId = $user->id;
            $userName = strtolower(trim($user->name));
            $clientsQuery->where(function ($q) use ($userId, $userName) {
                $q->where('collection_kam_id', $userId)
                  ->orWhereRaw('LOWER(collection_kam) = ?', [$userName])
                  ->orWhere('sm_kam_id', $userId)
                  ->orWhereRaw('LOWER(sm_kam) = ?', [$userName])
                  ->orWhere('nttn_billing_kam_id', $userId)
                  ->orWhereRaw('LOWER(nttn_billing_kam) = ?', [$userName])
                  ->orWhere('iig_itc_billing_kam_id', $userId)
                  ->orWhereRaw('LOWER(iig_itc_billing_kam) = ?', [$userName])
                  ->orWhere('collection_supervisor_id', $userId)
                  ->orWhereRaw('LOWER(collection_supervisor) = ?', [$userName])
                  ->orWhere('sm_supervisor_id', $userId)
                  ->orWhereRaw('LOWER(sm_supervisor) = ?', [$userName]);
            });
        }
        $clients = $clientsQuery->get();

        return view('waivers.index', [
            'waivers' => $waivers,
            'clients' => $clients,
            'selectedClientId' => $request->client_id,
            'selectedDateFrom' => $request->date_from,
            'selectedDateTo' => $request->date_to,
            'searchText' => $request->search,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:client,client_id'],
            'applicable_from_date' => ['required', 'date'],
            'waiver_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,gif,doc,docx,xls,xlsx'],
        ]);

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $dateClean = \Carbon\Carbon::parse($validated['applicable_from_date'])->format('Ymd');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'waiver' . $validated['client_id'] . $dateClean . ($ext ? '.' . $ext : '');
            $attachmentName = $fileName;
            
            $uploadDir = public_path('uploads/waivers');
            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }
            
            $file->move($uploadDir, $fileName);
            $attachmentPath = 'uploads/waivers/' . $fileName;
        }

        $creatorName = $request->user()?->name ?? $request->user()?->email ?? 'System';

        $waiver = Waiver::create([
            'client_id' => $validated['client_id'],
            'applicable_from_date' => $validated['applicable_from_date'],
            'waiver_amount' => $validated['waiver_amount'],
            'description' => $validated['description'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'created_by' => $creatorName,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Waiver entry created successfully.',
                'waiver' => $waiver,
            ]);
        }

        return redirect()->route('waivers.index')->with('status', 'Waiver entry created successfully.');
    }

    public function update(Request $request, Waiver $waiver): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:client,client_id'],
            'applicable_from_date' => ['required', 'date'],
            'waiver_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,gif,doc,docx,xls,xlsx'],
        ]);

        $attachmentPath = $waiver->attachment_path;
        $attachmentName = $waiver->attachment_name;

        if ($request->hasFile('attachment')) {
            if ($attachmentPath && File::exists(public_path($attachmentPath))) {
                File::delete(public_path($attachmentPath));
            }

            $file = $request->file('attachment');
            $dateClean = \Carbon\Carbon::parse($validated['applicable_from_date'])->format('Ymd');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'waiver' . $validated['client_id'] . $dateClean . ($ext ? '.' . $ext : '');
            $attachmentName = $fileName;
            
            $uploadDir = public_path('uploads/waivers');
            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }
            
            $file->move($uploadDir, $fileName);
            $attachmentPath = 'uploads/waivers/' . $fileName;
        }

        $waiver->update([
            'client_id' => $validated['client_id'],
            'applicable_from_date' => $validated['applicable_from_date'],
            'waiver_amount' => $validated['waiver_amount'],
            'description' => $validated['description'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Waiver entry updated successfully.',
            ]);
        }

        return redirect()->route('waivers.index')->with('status', 'Waiver entry updated successfully.');
    }

    public function destroy(Request $request, Waiver $waiver): RedirectResponse|JsonResponse
    {
        if ($waiver->attachment_path && File::exists(public_path($waiver->attachment_path))) {
            File::delete(public_path($waiver->attachment_path));
        }

        $waiver->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Waiver deleted successfully.',
            ]);
        }

        return redirect()->route('waivers.index')->with('status', 'Waiver deleted successfully.');
    }

    public function showAttachment(Waiver $waiver)
    {
        if (!$waiver->attachment_path || !File::exists(public_path($waiver->attachment_path))) {
            abort(404, 'Attachment file not found.');
        }

        $fullPath = public_path($waiver->attachment_path);
        $mimeType = File::mimeType($fullPath);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . ($waiver->attachment_name ?? basename($fullPath)) . '"',
        ]);
    }
}
