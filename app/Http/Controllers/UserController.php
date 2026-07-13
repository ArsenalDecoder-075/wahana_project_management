<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\ReportSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



class UserController extends Controller
{

    /*------------------------------------------
    Dashboard User (menampilkan daftar proyek)
    --------------------------------------------*/
    public function userDashboard(Request $request)
    {
        $userId = Auth::id();

        // Ambil semua proyek yang memiliki tugas yang ditugaskan ke user ini
        $projects = Project::whereHas('tasks', function ($query) use ($userId) {
            $query->where('assigned_to', $userId);
        })->with(['manager', 'tasks' => function ($query) use ($userId) {
            $query->where('assigned_to', $userId);
        }])->get();

        // Statistik sederhana untuk setiap proyek (opsional)
        foreach ($projects as $project) {
            $tasks = $project->tasks;
            $project->total_tasks = $tasks->count();
            $project->completed_tasks = $tasks->where('status', 'Completed')->count();
            $project->pending_tasks = $tasks->where('status', 'Pending')->count();
            $project->on_progress_tasks = $tasks->where('status', 'On Progress')->count();
            $project->progress_percentage = $project->total_tasks > 0
                ? round(($project->completed_tasks / $project->total_tasks) * 100)
                : 0;
        }

        return view('pages.user.dashboard.userHome', compact('projects'));
    }

    /*------------------------------------------
    --------------------------------------------
    Task Detail & Submission
    --------------------------------------------*/
    public function taskDetail($taskId)
    {
        $user = Auth::user();

        $task = Task::with(['project', 'assignee', 'creator'])
            ->where('id', $taskId)
            ->where('assigned_to', $user->id)
            ->firstOrFail();

        // Ambil submission terakhir untuk task ini
        $submission = \App\Models\TaskSubmission::where('task_id', $taskId)
            ->where('employee_id', $user->id)
            ->latest()
            ->first();

        return view('pages.user.tasks.detail', compact('task', 'submission'));
    }

    public function submitTaskNotes(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $task = Task::findOrFail($request->task_id);

        // Cek apakah task ini milik user yang login
        if ($task->assigned_to != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk tugas ini.'
            ], 403);
        }

        // Buat submission baru
        $submission = \App\Models\TaskSubmission::create([
            'task_id' => $task->id,
            'employee_id' => Auth::id(),
            'notes' => $request->notes,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catatan berhasil dikirim!',
            'data' => $submission
        ]);
    }



    public function tasksIndex()
    {
        $user = Auth::user();
        $tasks = Task::with(['project', 'assignee'])
            ->where('assigned_to', $user->id)
            ->orderBy('deadline', 'asc')
            ->get();

        return view('pages.user.tasks.index', compact('tasks'));
    }

    public function updateTaskStatus(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'status' => 'required|in:Pending,On Progress,Completed'
        ]);

        $task = Task::findOrFail($request->task_id);

        // Cek apakah tugas ini milik user yang login
        if ($task->assigned_to != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah tugas ini.'
            ], 403);
        }

        $task->status = $request->status;
        $task->save();

        return response()->json([
            'success' => true,
            'message' => 'Status tugas berhasil diupdate.'
        ]);
    }

    public function projectsIndex()
    {
        $user = Auth::user();

        // Ambil semua proyek yang memiliki tugas yang ditugaskan ke user ini
        $projects = \App\Models\Project::whereHas('tasks', function($query) use ($user) {
            $query->where('assigned_to', $user->id);
        })
        ->with(['tasks' => function($query) use ($user) {
            $query->where('assigned_to', $user->id);
        }, 'manager'])
        ->get()
        ->map(function($project) use ($user) {
            // Hitung statistik tugas untuk proyek ini
            $userTasks = $project->tasks->where('assigned_to', $user->id);
            $project->total_tasks = $userTasks->count();
            $project->pending_tasks = $userTasks->where('status', 'Pending')->count();
            $project->on_progress_tasks = $userTasks->where('status', 'On Progress')->count();
            $project->completed_tasks = $userTasks->where('status', 'Completed')->count();

            // Hitung progress user di proyek ini (berdasarkan bobot)
            $totalWeight = $userTasks->sum('weight');
            $completedWeight = $userTasks->where('status', 'Completed')->sum('weight');
            $project->user_progress = $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100) : 0;

            return $project;
        });

        return view('pages.user.projects.index', compact('projects'));
    }

    /*------------------------------------------
    Daftar Tugas dalam Proyek (filter user)
    --------------------------------------------*/
    public function projectTasks($projectId)
    {
        $userId = Auth::id();

        $project = Project::with(['manager'])
            ->whereHas('tasks', function ($query) use ($userId) {
                $query->where('assigned_to', $userId);
            })
            ->findOrFail($projectId);

        // Ambil tugas user dalam proyek ini
        $tasks = Task::with(['assignee', 'creator'])
            ->where('project_id', $projectId)
            ->where('assigned_to', $userId)
            ->orderBy('deadline', 'asc')
            ->get();

        return view('pages.user.tasks.index', compact('project', 'tasks'));
    }


    /*------------------------------------------
    --------------------------------------------
    profile & password
    --------------------------------------------*/
    public function profile()
    {
        // Ambil user dengan relasi yang diperlukan
        $user = User::with(['branch'])->find(Auth::id());

        return view('pages.user.profile', compact('user'));
    }
    public function changePassword()
    {
        return view('pages.user.change_password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Retrieve users based on the ID that is currently logged in
        $user = User::find(Auth::id());

        if (!$user) {
            return redirect()->route('user.changePassword')->with('error', 'User tidak ditemukan');
        }

        // Check if the old password matches
        if (Hash::check($request->old_password, $user->password)) {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Logout user after successful password change
            Auth::logout();

            // Invalidate the session
            $request->session()->invalidate();

            // Regenerate CSRF token
            $request->session()->regenerateToken();

            // Redirect to login page with success message
            return redirect()->route('login')->with('success', 'Kata sandi berhasil diubah. Silakan login kembali dengan kata sandi baru.');
        }

        return redirect()->route('user.changePassword')->with('error', 'Kata sandi lama salah.');
    }
}
