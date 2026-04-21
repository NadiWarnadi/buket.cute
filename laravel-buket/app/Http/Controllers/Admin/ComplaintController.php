<?php

namespace App\Http\Controllers\Admin;

use App\Models\Complaint;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        
        $query = Complaint::with(['customer', 'order'])->latest();
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $complaints = $query->paginate(20);
        
        return view('admin.complaints.index', compact('complaints', 'status'));
    }
    
    public function show(Complaint $complaint)
    {
        $complaint->load(['customer', 'order']);
        return view('admin.complaints.show', compact('complaint'));
    }
    
    public function update(Request $request, Complaint $complaint)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);
        
        $complaint->update([
            'status' => $request->status,
            'resolved_at' => $request->status === 'resolved' ? now() : null,
        ]);
        
        return redirect()->route('admin.complaints.index')
            ->with('success', 'Status komplain berhasil diupdate.');
    }
}