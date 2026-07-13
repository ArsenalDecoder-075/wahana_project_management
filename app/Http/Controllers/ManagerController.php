<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use App\Models\Project;
use App\Models\Task;

class ManagerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /*------------------------------------------
    --------------------------------------------
    Manager Home Reporting
    --------------------------------------------*/
    /**
     * Show the manager reporting dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function managerDashboard(Request $request)
    {
        // Get current manager
        $manager = Auth::user();

        return view('pages.manager.dashboard.managerHome');
    }

    /*------------------------------------------
    --------------------------------------------
    profile & password
    --------------------------------------------*/
    public function managerProfile()
    {
        $user = User::find(Auth::id());

        return view('pages.manager.managerprofile', compact('user'));
    }


    public function managerChangePassword()
    {
        return view('pages.manager.change_password');
    }
    public function managerUpdatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Retrieve users based on the ID that is currently logged in
        $user = User::find(Auth::id());

        if (!$user) {
            return redirect()->route('manager.changePassword')->with('error', 'User tidak ditemukan');
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

        return redirect()->route('manager.changePassword')->with('error', 'Kata sandi lama salah.');
    }

    /*------------------------------------------
    --------------------------------------------
    Hierarki Management
    --------------------------------------------
    --------------------------------------------*/

    public function manageHierarchy(Request $request)
    {
        // Ambil ID manager yang sedang login
        $managerId = Auth::id();

        if ($request->ajax()) {
            // HANYA tampilkan employee yang manager_id-nya = manager yang login
            $data = User::select([
                    'users.id as employee_id',
                    'users.name as employee_name',
                    'users.branch_id',
                    'manager_employees.manager_id',
                    'managers.name as manager_name'
                ])
                ->with('branch')
                ->join('manager_employees', 'users.id', '=', 'manager_employees.employee_id')
                ->join('users as managers', 'manager_employees.manager_id', '=', 'managers.id')
                ->where('users.type', '0')
                ->where('manager_employees.manager_id', $managerId) // ✅ FILTER: hanya bawahan manager ini
                ->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('employee_id', function ($row) { return $row->employee_id; })
                ->addColumn('employee_name', function ($row) { return $row->employee_name; })
                ->addColumn('branch_name', function ($row) { return $row->branch ? $row->branch->name : '-'; })
                ->addColumn('manager_id', function ($row) { return $row->manager_id; })
                ->addColumn('manager_name', function ($row) { return $row->manager_name; })
                ->addColumn('action', function ($row) {
                    return '
                    <div class="btn-group">
                        <button class="btn btn-sm btn-danger unassignBtn"
                            data-id="'.$row->employee_id.'"
                            data-name="'.$row->employee_name.'"
                            title="Cabut Manajer">
                            <i class="fa fa-user-minus"></i> Cabut
                        </button>
                    </div>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        // Dropdown Karyawan yang BELUM PUNYA manajer (untuk ditambahkan)
        $availableEmployees = User::with('branch')
            ->where('type', '0')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('manager_employees')
                    ->whereColumn('manager_employees.employee_id', 'users.id');
            })
            ->orderBy('name')
            ->get();

        // Hanya tampilkan manager yang login di dropdown (tidak bisa pilih manager lain)
        $managers = User::where('id', $managerId)
            ->whereIn('type', ['1', '2'])
            ->orderBy('name')
            ->get();

        return view('pages.manager.managehierarchy', compact('availableEmployees', 'managers'));
    }
    public function storeHierarchy(Request $request)
    {
        $managerId = Auth::id();

        $request->validate([
            'employee_id' => 'required|exists:users,id',
            // manager_id tidak perlu divalidasi karena otomatis dari session
        ]);

        // Cek apakah employee sudah punya manager
        $existing = DB::table('manager_employees')
            ->where('employee_id', $request->employee_id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan ini sudah memiliki manajer!'
            ], 422);
        }

        // Cek apakah employee adalah tipe user (bukan admin/manager)
        $employee = User::find($request->employee_id);
        if ($employee->type != 0) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya karyawan (type 0) yang bisa menjadi bawahan!'
            ], 422);
        }

        // Tambahkan ke tabel manager_employees dengan manager_id = user yang login
        DB::table('manager_employees')->insert([
            'employee_id' => $request->employee_id,
            'manager_id' => $managerId, // ✅ Otomatis pakai manager yang login
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil ditambahkan sebagai bawahan!'
        ]);
    }

    public function unassignHierarchy(Request $request)
    {
        $managerId = Auth::id();

        $request->validate([
            'employee_id' => 'required|exists:users,id',
        ]);

        // Cek apakah employee adalah bawahan dari manager yang login
        $existing = DB::table('manager_employees')
            ->where('employee_id', $request->employee_id)
            ->where('manager_id', $managerId)
            ->first();

        if (!$existing) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan ini bukan bawahan Anda!'
            ], 403);
        }

        // Hapus relasi
        DB::table('manager_employees')
            ->where('employee_id', $request->employee_id)
            ->where('manager_id', $managerId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil dicabut dari bawahan Anda!'
        ]);
    }

    /*------------------------------------------
    --------------------------------------------
    Project Management
    --------------------------------------------
    --------------------------------------------*/

    public function manageProject(Request $request)
    {
        $managerId = Auth::id();

        if ($request->ajax()) {
            // ✅ Gunakan query builder tanpa ->get() untuk DataTables serverSide
            $projects = Project::with('manager')
                ->where('manager_id', $managerId)
                ->select('projects.*'); // Select semua kolom dari projects

            return datatables()->of($projects)
                ->addIndexColumn()
                ->addColumn('manager', function($row) {
                    return $row->manager ? $row->manager->name : '-';
                })
                ->addColumn('duration', function($row) {
                    return $row->start_date . ' s/d ' . $row->end_date;
                })
                // ->addColumn('progress', function($row) {
                //     return '<div class="progress" style="height: 15px;">
                //                 <div class="progress-bar bg-success" role="progressbar"
                //                     style="width: '.$row->progress.'%"
                //                     aria-valuenow="'.$row->progress.'"
                //                     aria-valuemin="0"
                //                     aria-valuemax="100">'.$row->progress.'%</div>
                //             </div>';
                // })
                ->addColumn('total_tasks', function($row) {
                    $total = $row->tasks->count();
                    $completed = $row->tasks->where('status', 'Completed')->count();

                    if ($total == 0) {
                        return '<span class="text-muted"><i class="fas fa-minus"></i> 0 tugas</span>';
                    }

                    return '<span class="fw-bold">Jumlah Tugas : ' . $total . '</span>
                            <span class="text-muted"> | Tugas Selesai: ' . $completed . ')</span>';
                })
                ->addColumn('status_badge', function($row) {
                    $colors = [
                        'Pending' => 'warning',
                        'On Progress' => 'info',
                        'Completed' => 'success'
                    ];
                    $color = $colors[$row->status] ?? 'secondary';
                    return '<span class="badge bg-'.$color.'">'.$row->status.'</span>';
                })
                ->addColumn('action', function($row) {
                    return '
                    <div class="btn-group" role="group">
                        <a href="'.route('manager.tasks.manage', $row->id).'" class="btn btn-info btn-sm">
                            <i class="lni lni-plus"></i> Kelola Tugas
                        </a>
                        <button class="btn btn-warning btn-sm editProjectBtn"
                            data-id="'.$row->id.'"
                            data-title="'.$row->title.'"
                            data-description="'.$row->description.'"
                            data-start="'.$row->start_date.'"
                            data-end="'.$row->end_date.'"
                            data-status="'.$row->status.'"
                            title="Edit Proyek">
                            <i class="lni lni-pencil"></i>
                        </button>
                        <button class="btn btn-danger btn-sm deleteProjectBtn"
                            data-id="'.$row->id.'"
                            data-title="'.$row->title.'"
                            title="Hapus Proyek">
                            <i class="lni lni-trash-can"></i>
                        </button>
                    </div>';
                })
                ->rawColumns(['total_tasks', 'status_badge', 'action'])
                ->make(true); // ✅ make(true) akan mengembalikan JSON response yang valid
        }

        // Untuk dropdown di modal tambah tugas - hanya karyawan bawahan manager ini
        $employees = User::whereIn('id', function($query) use ($managerId) {
            $query->select('employee_id')
                ->from('manager_employees')
                ->where('manager_id', $managerId);
        })->where('type', '0')
        ->orderBy('name')
        ->get();

        // Untuk dropdown di modal tambah proyek - hanya manager yang login
        $managers = User::where('id', $managerId)
            ->whereIn('type', ['1', '2'])
            ->orderBy('name')
            ->get();

        return view('pages.manager.manageproject', compact('employees', 'managers'));
    }

    public function storeProject(Request $request)
    {
        $managerId = Auth::id();

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
        ]);

        // Simpan proyek dengan manager_id = user yang login
        Project::create([
            'manager_id'  => $managerId, // ✅ Otomatis pakai manager yang login
            'title'       => $request->title,
            'description' => $request->description,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'progress'    => 0,
            'status'      => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proyek baru berhasil dibuat!'
        ]);
    }

    public function updateProject(Request $request)
    {
        $managerId = Auth::id();

        $request->validate([
            'project_id'  => 'required|exists:projects,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'status'      => 'required|in:Pending,On Progress,Completed',
        ]);

        $project = Project::findOrFail($request->project_id);

        // ✅ Validasi: hanya manager pemilik proyek yang bisa update
        if ($project->manager_id != $managerId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengupdate proyek ini!'
            ], 403);
        }

        $project->update([
            'title'       => $request->title,
            'description' => $request->description,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'status'      => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data proyek berhasil diperbarui!'
        ]);
    }

    public function deleteProject(Request $request)
    {
        $managerId = Auth::id();

        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($request->project_id);

        // ✅ Validasi: hanya manager pemilik proyek yang bisa hapus
        if ($project->manager_id != $managerId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus proyek ini!'
            ], 403);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proyek berhasil dihapus!'
        ]);
    }

    public function manageTasks(Request $request, $project_id)
    {
        $managerId = Auth::id();

        $project = Project::with('manager')->findOrFail($project_id);

        // ✅ Validasi: hanya manager pemilik proyek yang bisa akses
        if ($project->manager_id != $managerId) {
            abort(403, 'Anda tidak memiliki akses ke proyek ini!');
        }

        // Hanya karyawan bawahan manager ini
        $employees = User::whereIn('id', function ($query) use ($managerId) {
            $query->select('employee_id')
                ->from('manager_employees')
                ->where('manager_id', $managerId);
        })->where('type', '0')
        ->orderBy('name')
        ->get();

        // Hitung jumlah tugas per karyawan untuk proyek ini
        $employeeTaskCounts = Task::where('project_id', $project_id)
        ->select('assigned_to', DB::raw('count(*) as total'))
        ->groupBy('assigned_to')
        ->pluck('total', 'assigned_to')
        ->toArray();

        // Ambil semua tugas proyek untuk statistik
        $allTasks = Task::where('project_id', $project_id)->get();

        // Hitung statistik per prioritas
        $priorityStats = [];
        $priorities = ['low', 'medium', 'high'];
        foreach ($priorities as $priority) {
            $total = $allTasks->where('priority', $priority)->count();
            $completed = $allTasks->where('priority', $priority)
                                ->where('status', 'Completed')
                                ->count();
            $priorityStats[$priority] = [
                'total'     => $total,
                'completed' => $completed,
            ];
        }

        $totalTasks     = $allTasks->count();
        $totalCompleted = $allTasks->where('status', 'Completed')->count();

        if ($request->ajax()) {
            $tasks = Task::with('assignee')
                ->where('project_id', $project_id)
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low') ASC")
                ->orderBy('created_at', 'DESC') // opsional: jika priority sama, urutkan berdasarkan created_at
                ->get();

            return datatables()->of($tasks)
                ->addIndexColumn()
                ->addColumn('description', function($row) {
                    if (empty($row->description)) {
                        return '<span class="text-muted"><i class="fas fa-minus"></i> Tidak ada deskripsi</span>';
                    }
                    // Tampilkan seluruh teks dengan word-wrap
                    return '<div style="word-wrap: break-word; white-space: normal;">'
                            . nl2br(e($row->description)) . '</div>';
                })
                ->addColumn('employee_name', function($row) {
                    return $row->assignee ? $row->assignee->name : 'Tidak Ada';
                })
                ->addColumn('priority_badge', function($row) {
                    $badges = [
                        'high'   => '<span class="badge bg-danger"><i class="fas fa-arrow-up"></i> HIGH</span>',
                        'medium' => '<span class="badge bg-warning text-dark"><i class="fas fa-minus"></i> MEDIUM</span>',
                        'low'    => '<span class="badge bg-info"><i class="fas fa-arrow-down"></i> LOW</span>'
                    ];
                    return $badges[$row->priority] ?? '<span class="badge bg-secondary">UNKNOWN</span>';
                })
                ->addColumn('deadline_format', function ($row) {
                    return \Carbon\Carbon::parse($row->deadline)->format('d M Y');
                })
                ->addColumn('weight_display', function ($row) {
                    return $row->weight . '%';
                })
                ->addColumn('status_badge', function ($row) {
                    $colors = [
                        'Pending' => 'warning',
                        'On Progress' => 'info',
                        'Completed' => 'success'
                    ];
                    $color = $colors[$row->status] ?? 'secondary';
                    return '<span class="badge bg-'.$color.'">'.$row->status.'</span>';
                })
                ->addColumn('action', function($row) use ($project_id) {
                    return '
                    <div class="btn-group">
                        <button class="btn btn-sm btn-warning editTaskBtn"
                            data-id="'.$row->id.'"
                            data-title="'.$row->title.'"
                            data-weight="'.$row->weight.'"
                            data-assigned="'.$row->assigned_to.'"
                            data-deadline="'.$row->deadline.'"
                            data-status="'.$row->status.'">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deleteTaskBtn"
                            data-id="'.$row->id.'"
                            data-title="'.$row->title.'">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                    ';
                })
                ->rawColumns(['description', 'priority_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('pages.manager.managetask', compact('project', 'employees', 'priorityStats', 'totalTasks', 'employeeTaskCounts', 'totalCompleted'));
    }

    /**
     * Store tugas baru
     */
    public function storeTask(Request $request)
    {
        $managerId = Auth::id();

        $project = Project::findOrFail($request->project_id);

        $request->validate([
            'project_id'  => 'required|exists:projects,id',
            'assigned_to' => 'required|exists:users,id',
            'title'       => 'required|string|max:255',
            'priority'    => 'required|in:low,medium,high',
            'deadline'    => 'required|date|after_or_equal:' . $project->start_date,
            'description' => 'nullable|string'
        ]);

        // ✅ Cek apakah proyek milik manager ini
        $project = Project::findOrFail($request->project_id);
        if ($project->manager_id != $managerId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke proyek ini!'
            ], 403);
        }

        // ✅ Cek apakah karyawan adalah bawahan manager ini
        $isSubordinate = DB::table('manager_employees')
            ->where('employee_id', $request->assigned_to)
            ->where('manager_id', $managerId)
            ->exists();

        if (!$isSubordinate) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan ini bukan bawahan Anda!'
            ], 403);
        }

        // Simpan tugas
        Task::create([
            'project_id'  => $request->project_id,
            'assigned_to' => $request->assigned_to,
            'title'       => $request->title,
            'priority'     => $request->priority,
            'deadline'     => $request->deadline,
            'description'  => $request->description,
            'status'      => 'Pending',
            'created_by'  => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil ditambahkan!'
        ]);
    }

    /**
     * Update tugas
     */
    public function updateTask(Request $request)
    {
        $managerId = Auth::id();

        $request->validate([
            'id'          => 'required|exists:tasks,id',
            'title'       => 'required|string|max:255',
            'weight'      => 'required|numeric|min:1|max:100',
            'assigned_to' => 'required|exists:users,id',
            'deadline'    => 'required|date',
            'status'      => 'required|in:Pending,On Progress,Completed'
        ]);

        $task = Task::findOrFail($request->id);

        // ✅ Cek apakah proyek milik manager ini
        $project = Project::findOrFail($task->project_id);
        if ($project->manager_id != $managerId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke tugas ini!'
            ], 403);
        }

        // ✅ Cek apakah karyawan adalah bawahan manager ini
        $isSubordinate = DB::table('manager_employees')
            ->where('employee_id', $request->assigned_to)
            ->where('manager_id', $managerId)
            ->exists();

        if (!$isSubordinate) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan ini bukan bawahan Anda!'
            ], 403);
        }

        // Validasi bobot 100%
        $others = Task::where('project_id', $task->project_id)
            ->where('id', '!=', $task->id)
            ->sum('weight');

        if (($others + $request->weight) > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Total bobot melebihi 100%! Bobot tersisa: ' . (100 - $others) . '%'
            ], 422);
        }

        $task->update([
            'title'       => $request->title,
            'weight'      => $request->weight,
            'assigned_to' => $request->assigned_to,
            'deadline'    => $request->deadline,
            'status'      => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil diupdate!'
        ]);
    }

    /**
     * Delete tugas
     */
    public function deleteTask(Request $request)
    {
        $managerId = Auth::id();

        $request->validate([
            'id' => 'required|exists:tasks,id'
        ]);

        $task = Task::findOrFail($request->id);

        // ✅ Cek apakah proyek milik manager ini
        $project = Project::findOrFail($task->project_id);
        if ($project->manager_id != $managerId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke tugas ini!'
            ], 403);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dihapus!'
        ]);
    }



}
