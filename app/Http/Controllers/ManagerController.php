<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use App\Models\Project;
use App\Models\TaskSubmission;
use App\Models\Task;
use App\Models\TaskReview;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


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
        $managerId = Auth::id();
        $projects = Project::where('manager_id', $managerId)->get();

        // Notif Review
        $reviewProjects = [];
        $totalReviewTasks = 0;

        foreach ($projects as $project) {
            $reviewCount = Task::where('project_id', $project->id)
                ->where('status', 'Review')
                ->count();

            if ($reviewCount > 0) {
                $reviewProjects[] = [
                    'project' => $project,
                    'review_count' => $reviewCount
                ];
                $totalReviewTasks += $reviewCount;
            }
        }

        // Grafik keterampilan pekerja bawahan
        $employees = User::whereIn('id', function ($query) use ($managerId) {
            $query->select('employee_id')
                ->from('manager_employees')
                ->where('manager_id', $managerId);
        })->where('type', 0)->get();

        $employeeStats = [];
        $chartLabels = [];
        $chartCompleted = [];
        $chartPending = [];

        foreach ($employees as $employee) {
            $totalTasks = Task::where('assigned_to', $employee->id)->count();
            $completedTasks = Task::where('assigned_to', $employee->id)
                ->where('status', 'Completed')
                ->count();
            $pendingTasks = $totalTasks - $completedTasks;

            $employeeStats[] = [
                'employee' => $employee,
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'pending' => $pendingTasks,
                'progress' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
            ];

            $chartLabels[] = $employee->name;
            $chartCompleted[] = $completedTasks;
            $chartPending[] = $pendingTasks;
        }

        // Progress setiap proyek yang dikelola manager ini
        $projectProgress = [];
        foreach ($projects as $project) {
            $totalTasks = Task::where('project_id', $project->id)->count();
            $completedTasks = Task::where('project_id', $project->id)
                ->where('status', 'Completed')
                ->count();

            $projectProgress[] = [
                'project' => $project,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'progress' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
                'status' => $project->status
            ];
        }

        // CALENDAR WIDGET - Tugas per Hari
        $calendarTasks = [];
        $currentMonth = $request->input('month', date('Y-m'));
        $monthStart = date('Y-m-01', strtotime($currentMonth));
        $monthEnd = date('Y-m-t', strtotime($currentMonth));

        // Ambil semua tugas dari proyek manager ini
        $tasks = Task::whereIn('project_id', $projects->pluck('id'))
            ->with(['project', 'assignee'])
            ->whereBetween('deadline', [$monthStart, $monthEnd])
            ->orderBy('deadline')
            ->get();

        // Kelompokkan tugas berdasarkan tanggal deadline
        foreach ($tasks as $task) {
            $date = $task->deadline;
            if (!isset($calendarTasks[$date])) {
                $calendarTasks[$date] = [];
            }
            $calendarTasks[$date][] = [
                'id' => $task->id,
                'title' => $task->title,
                'project' => $task->project->title,
                'assignee' => $task->assignee->name ?? 'Unassigned',
                'status' => $task->status,
                'priority' => $task->priority,
                'deadline' => $task->deadline,
                'time' => $task->created_at->format('H:i')
            ];
        }

        return view('pages.manager.dashboard.managerHome', compact(
            'reviewProjects',
            'totalReviewTasks',
            'employeeStats',
            'chartLabels',
            'chartCompleted',
            'chartPending',
            'projectProgress',
            'calendarTasks',      // data untuk calendar
            'currentMonth'        // bulan yang sedang aktif
        ));
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
            $projects = Project::with('manager', 'category')
                ->where('manager_id', $managerId)
                ->select('projects.*'); // Select semua kolom dari projects

            return datatables()->of($projects)
                ->addIndexColumn()
                ->addColumn('manager', function($row) {
                    return $row->manager ? $row->manager->name : '-';
                })
                // ->addColumn('duration', function($row) {
                //     return $row->start_date . ' s/d ' . $row->end_date;
                // })
                // ->addColumn('title', function($row) {
                //     $title = '<div class="fw-bold text-dark mb-1">' . e($row->title) . '</div>';
                //     $description = '<div class="text-muted small">' . e($row->description) . '</div>';
                //     return $title . $description;
                // })
                ->addColumn('title', function($row) {
                    // Buat ID unik untuk collapse
                    $collapseId = 'descCollapse_' . $row->id;

                    $html = '
                    <div class="project-title-wrapper">
                        <div class="fw-bold text-dark mb-1" data-bs-toggle="collapse"
                        data-bs-target="#' . $collapseId . '" aria-expanded="false">
                        ' . e($row->title) . '
                        </div>

                        <!-- Deskripsi Proyek (Tersembunyi, akan muncul saat diklik) -->
                        <div class="collapse mt-2" id="' . $collapseId . '">
                        <p class="mb-0 text-dark">' . e($row->description) . '</p>
                        </div>
                    </div>
                    ';

                    return $html;
                })
                ->addColumn('duration', function($row) {
                    // Format: 12 Agu 2026 (3 huruf awal bulan)
                    $start = \Carbon\Carbon::parse($row->start_date)->format('d M Y');
                    $end = \Carbon\Carbon::parse($row->end_date)->format('d M Y');

                    return '
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary text-white px-3 py-2 rounded-pill">
                                <i class="fas fa-calendar-day me-1"></i> ' . $start . '
                            </span>
                            <span class="badge bg-danger text-white px-3 py-2 rounded-pill">
                                <i class="fas fa-calendar-times me-1"></i> ' . $end . '
                            </span>
                        </div>
                    ';
                })
                ->addColumn('category_name', function($row) {
                    return '<span class="fw-bold">' . ($row->category?->name ?? 'Tidak Ada') . '</span>';
                })
                ->addColumn('total_tasks', function($row) {
                    $total = $row->tasks->count();
                    $completed = $row->tasks->where('status', 'Completed')->count();

                    if ($total == 0) {
                        return '<span class="text-muted"><i class="fas fa-minus"></i> 0 tugas</span>';
                    }

                    // return '<span class="fw-bold">Jumlah Tugas : ' . $total . '</span>
                    //         <span class="text-muted"> | Tugas Selesai: ' . $completed . ')</span>';

                    return '
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-info text-white px-3 py-2 rounded-pill">
                            <i class="fa-regular fa-square-check"></i></i> Jumlah Tugas : ' . $total . '
                            </span>
                            <span class="badge bg-success text-white px-3 py-2 rounded-pill">
                            <i class="fa-regular fa-square"></i></i> Tugas Selesai : ' . $completed . '
                            </span>
                        </div>
                    ';
                })
                ->addColumn('status_badge', function($row) {
                    $colors = [
                        'Pending' => 'warning',
                        'On Progress' => 'info',
                        'Completed' => 'success',
                        'Rejected' => 'danger'
                    ];
                    $color = $colors[$row->status] ?? 'secondary';
                    return '<span class="badge bg-'.$color.'">'.$row->status.'</span>';
                })
                ->addColumn('action', function($row) {
                    return '
                    <div class="btn-group" role="group">
                        <a href="'.route('manager.tasks.manage', $row->id).'" class="btn btn-info btn-sm" title="Kelola Tugas Proyek">
                            <i class="lni lni-plus"></i>
                        </a>
                        <button class="btn btn-warning btn-sm editProjectBtn"
                            data-id="'.$row->id.'"
                            data-title="'.$row->title.'"
                            data-description="'.$row->description.'"
                            data-start="'.$row->start_date.'"
                            data-end="'.$row->end_date.'"
                            data-category-id="'.$row->category_id.'"
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
                ->rawColumns(['title', 'duration', 'total_tasks', 'status_badge', 'category_name',  'action'])
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

        $categories = Category::where('is_active', true)->get(); // ✅ Gunakan nama yang tepat

        return view('pages.manager.manageproject', compact('employees', 'managers', 'categories'));
    }

    public function storeProject(Request $request)
    {
        $managerId = Auth::id();

        $request->validate([
            'title'       => 'required|string|max:255',
            'category_id'  => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
        ]);

        // Simpan proyek dengan manager_id = user yang login
        Project::create([
            'manager_id'  => $managerId, // ✅ Otomatis pakai manager yang login
            'category_id'  => $request->category_id,
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
            'category_id'  => 'required|exists:categories,id',
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
            'category_id'  => $request->category_id,
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

        $project = Project::with('manager', 'category')->findOrFail($project_id);

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
                ->addColumn('startdate_format', function ($row) {
                    if (empty($row->start_date)) {
                        return '-';
                    }
                    return \Carbon\Carbon::parse($row->start_date)->format('d M Y');
                })
                ->addColumn('deadline_format', function ($row) {
                    return \Carbon\Carbon::parse($row->deadline)->format('d M Y');
                })
                ->addColumn('weight_display', function ($row) {
                    return $row->weight . '%';
                })
                // ->addColumn('status_badge', function ($row) {
                //     $colors = [
                //         'Pending' => 'warning',
                //         'On Progress' => 'info',
                //         'Completed' => 'success',
                //         'Rejected' => 'danger'
                //     ];
                //     $color = $colors[$row->status] ?? 'secondary';
                //     return '<span class="badge bg-'.$color.'">'.$row->status.'</span>';
                // })
                ->addColumn('status_badge', function($row) {
                    $badges = [
                        'Pending'     => '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>',
                        'On Progress' => '<span class="badge bg-primary"><i class="fas fa-spinner fa-spin"></i> On Progress</span>',
                        'Completed'   => '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Completed</span>',
                        'Review'    => '<span class="badge bg-secondary"><i class="fa-solid fa-magnifying-glass"></i> Review</span>',
                        'Rejected'    => '<span class="badge bg-danger"><i class="fas fa-undo"></i> Rejected</span>'
                    ];
                    return $badges[$row->status] ?? '<span class="badge bg-secondary">' . $row->status . '</span>';
                })
                ->addColumn('action', function($row) {
                    $route = Auth::user()->type == 1 ? 'admin.tasks.review' : 'manager.tasks.review';

                    // ✅ Review button: hanya muncul jika status 'Review' atau 'Completed'
                    $reviewBtn = '';
                    if (in_array($row->status, ['On Progress', 'Review', 'Completed'])) {
                        $reviewBtn = '
                            <a href="'.route($route, [
                                'project_id' => $row->project_id,
                                'task_id' => $row->id
                            ]).'"
                               class="btn btn-sm btn-success"
                               title="Lihat/Review Submission">
                                <i class="fas fa-check-double me-1"></i> Review
                            </a>
                        ';
                    }

                    return '
                    <div class="btn-group">
                        <button class="btn btn-sm btn-warning editTaskBtn"
                            data-id="'.$row->id.'"
                            data-title="'.addslashes($row->title).'"
                            data-description="'.addslashes($row->description).'"
                            data-priority="'.$row->priority.'"
                            data-assigned="'.$row->assigned_to.'"
                            data-start_date="'.$row->start_date.'"
                            data-deadline="'.$row->deadline.'"
                            data-status="'.$row->status.'">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deleteTaskBtn"
                            data-id="'.$row->id.'"
                            data-title="'.addslashes($row->title).'"
                            data-description="'.addslashes($row->description).'"
                            data-employee="'.($row->assignee?->name ?? 'Tidak Ada').'"
                            data-priority="'.$row->priority.'"
                            data-start_date="'.$row->start_date.'"
                            data-deadline="'.$row->deadline.'"
                            data-status="'.$row->status.'"
                            title="Hapus Tugas">
                            <i class="fas fa-trash"></i>
                        </button>
                        '.$reviewBtn.'
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
            'startdate'   => 'required|date|after_or_equal:' . $project->start_date,
            'deadline'    => [
                'required',
                'date',
                'after_or_equal:' . $project->start_date,
                'before_or_equal:' . $project->end_date,
                'after_or_equal:startdate',
            ],
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
            'priority'    => $request->priority,
            'start_date'  => $request->startdate,
            'deadline'    => $request->deadline,
            'description' => $request->description,
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
        $task = Task::findOrFail($request->id);
        $project = $task->project;

        $request->validate([
            'id'          => 'required|exists:tasks,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority'    => 'required|in:low,medium,high',
            'startdate'   => 'required|date|after_or_equal:' . $project->start_date,
            'deadline'    => [
                'required',
                'date',
                'after_or_equal:startdate',
                'before_or_equal:' . $project->end_date,
            ],
            'assigned_to' => 'required|exists:users,id',
            'status'      => 'nullable|in:Pending,On Progress,Completed,Revision'
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

        $task->update([
            'title'       => $request->title,
            'description' => $request->description,
            'priority'    => $request->priority,
            'start_date'  => $request->startdate,
            'deadline'    => $request->deadline,
            'assigned_to' => $request->assigned_to,
            'status'      => $request->status ?? $task->status
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

    public function taskReview($project_id, $task_id)
    {
        $userId = Auth::id();
        $userType = Auth::user()->type;

        $project = Project::with('manager')->findOrFail($project_id);
        $task = Task::with(['assignee', 'creator'])->findOrFail($task_id);

        // Ambil submission urut dari yang paling LAMA ke yang terbaru (asc)
        $submissions = $task->submissions()
            ->with(['employee', 'reviews.reviewer', 'files']) // tambahkan 'files'
            ->where('task_id', $task_id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Cek akses (hanya manager yang berhak)
        if ($userType == 2 && $project->manager_id != $userId) {
            abort(403, 'Anda tidak memiliki akses ke proyek ini!');
        }

        return view('pages.manager.submissions.review', compact('project', 'task', 'submissions'));
    }

    public function reviewSubmission(Request $request)
    {
        $request->validate([
            'submission_id' => 'required|exists:task_submissions,id',
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $userId = Auth::id();
        $userType = Auth::user()->type;
        $submission = TaskSubmission::with(['task', 'task.project'])
            ->findOrFail($request->submission_id);

        // Validasi akses
        if ($userType == 2 && $submission->task->project->manager_id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke submission ini!'
            ], 403);
        }

        // Cek apakah sudah ada review
        if ($submission->review()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Submission ini sudah direview sebelumnya.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Buat review baru di tabel task_reviews
            $review = TaskReview::create([
                'submission_id' => $request->submission_id,
                'manager_id' => $userId,
                'notes' => $request->review_notes,
                'status' => $request->status,
                'reviewed_at' => now(),
            ]);

            // Update status tugas
            $task = $submission->task;
            if ($request->status == 'approved') {
                $task->update(['status' => 'Completed']);
            } else {
                $task->update(['status' => 'On Progress']);
            }

            // Update progress proyek (tanpa weight)
            $this->updateProjectProgress($submission->task->project_id);

            DB::commit();

            $route = $userType == 1 ? 'admin.tasks.manage' : 'manager.tasks.manage';

            return response()->json([
                'success' => true,
                'message' => $request->status == 'approved'
                    ? '✅ Submission disetujui! Tugas selesai.'
                    : '❌ Submission ditolak. Tugas dikembalikan ke On Progress.',
                'redirect_url' => route($route, $submission->task->project_id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Review submission error: ' . $e->getMessage(), [
                'submission_id' => $request->submission_id,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses review. Silakan coba lagi.'
            ], 500);
        }
    }

    private function updateProjectProgress($projectId)
    {
        $total = Task::where('project_id', $projectId)->count();
        $completed = Task::where('project_id', $projectId)->where('status', 'Completed')->count();

        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;

        Project::where('id', $projectId)->update(['progress' => $progress]);

        // Update status proyek
        if ($progress == 100) {
            Project::where('id', $projectId)->update(['status' => 'Completed']);
        } elseif ($progress > 0) {
            Project::where('id', $projectId)->update(['status' => 'On Progress']);
        }
    }

    public function updateTaskStatus(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'status' => 'required|in:On Progress,Review,Completed,Rejected'
        ]);

        $task = Task::findOrFail($request->task_id);
        $task->update(['status' => $request->status]);

        // Update progress proyek
        $this->updateProjectProgress($task->project_id);

        return response()->json([
            'success' => true,
            'message' => 'Status tugas berhasil diperbarui menjadi ' . $request->status
        ]);
    }

    /**
     * Update review feedback
     */
    public function updateReview(Request $request)
    {
        $request->validate([
            'submission_id' => 'required|exists:task_submissions,id',
            'review_notes' => 'nullable|string|max:1000'
        ]);

        $submission = TaskSubmission::findOrFail($request->submission_id);
        $review = $submission->review;

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review tidak ditemukan untuk submission ini.'
            ], 404);
        }

        $review->update(['notes' => $request->review_notes]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback review berhasil diperbarui.'
        ]);
    }

    public function addReviewFeedback(Request $request)
    {
        $request->validate([
            'submission_id' => 'required|exists:task_submissions,id',
            'feedback_notes' => 'required|string|max:1000',
            'status' => 'required|in:accepted,revision needed,rejected' // Validasi status
        ]);

        $userId = Auth::id();
        $submission = TaskSubmission::with('task.project')->findOrFail($request->submission_id);

        // Cek akses
        if (Auth::user()->type == 2 && $submission->task->project->manager_id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke submission ini!'
            ], 403);
        }

        // Cek apakah sudah ada review
        if ($submission->review()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Submission ini sudah memiliki feedback.'
            ], 422);
        }

        // Simpan review
        TaskReview::create([
            'submission_id' => $request->submission_id,
            'manager_id' => $userId,
            'feedback_notes' => $request->feedback_notes,
            'status' => $request->status, // Gunakan status dari request
            'reviewed_at' => now(),
        ]);

        $message = match($request->status) {
            'accepted' => '✅ Feedback berhasil! Submission disetujui.',
            'revision needed' => '🔄 Feedback berhasil! Submission butuh revisi.',
            'rejected' => '❌ Feedback berhasil! Submission ditolak.',
            default => 'Feedback berhasil ditambahkan.'
        };

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    // Halaman kalender
    public function calendar(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        return view('pages.manager.calendar', compact('year', 'month'));
    }

    // Ambil data buat halaman calender
    public function calendarData(Request $request)
    {
        $managerId = Auth::id();
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));

        $projects = Project::where('manager_id', $managerId)->pluck('id');

        // Tentukan rentang bulan yang sedang dilihat
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $tasks = Task::whereIn('project_id', $projects)
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->where('start_date', '<=', $monthEnd)
                    ->where('deadline', '>=', $monthStart);
            })
            ->with(['project', 'assignee'])
            ->get();

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::create($year, $month, $d)->format('Y-m-d');
            $days[$d] = [
                'date' => $date,
                'day' => $d,
                'dayName' => Carbon::create($year, $month, $d)->isoFormat('dddd'),
            ];
        }

        // Prioritas yang diinginkan (Urutan kiri ke kanan: Low, Medium, High)
        $priorityOrder = ['low', 'medium', 'high'];
        $eventsByPriority = [];
        $totalRowsByPriority = [];

        foreach ($priorityOrder as $priority) {
            // Filter tasks berdasarkan prioritas saat ini
            $filteredTasks = $tasks->filter(function ($task) use ($priority) {
                return $task->priority === $priority;
            })->values();

            // Persiapkan data event
            $events = [];
            foreach ($filteredTasks as $task) {
                $start = Carbon::parse($task->start_date);
                $end = Carbon::parse($task->deadline);

                $startCol = ($start->month == $month) ? $start->day : 1;
                $endCol = ($end->month == $month) ? $end->day : $daysInMonth;

                if ($startCol < 1) $startCol = 1;
                if ($endCol > $daysInMonth) $endCol = $daysInMonth;

                $events[] = [
                    'id' => $task->id,
                    'project_id' => $task->project_id,
                    'title' => $task->title,
                    'project' => $task->project->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'assignee' => $task->assignee->name ?? '-',
                    'start_col' => $startCol,
                    'end_col' => $endCol,
                ];
            }

            // Gunakan fungsi groupTasksByRow untuk menangani tumpang tindih di dalam satu prioritas
            $groupedRows = $this->groupTasksByRow($events);
            $totalRows = count($groupedRows);

            // Ratakan array dan tambahkan row_index
            $flattenedEvents = [];
            foreach ($groupedRows as $rowIdx => $row) {
                foreach ($row as $task) {
                    $task['row_index'] = $rowIdx;
                    $flattenedEvents[] = $task;
                }
            }

            $eventsByPriority[$priority] = $flattenedEvents;
            $totalRowsByPriority[$priority] = $totalRows;
        }

        return response()->json([
            'days' => array_values($days),
            'events_by_priority' => $eventsByPriority, // Data event per prioritas
            'total_rows_by_priority' => $totalRowsByPriority, // Maks overlap per kolom
            'monthLabel' => Carbon::create($year, $month, 1)->format('F Y'),
            'year' => $year,
            'month' => $month,
        ]);
    }


    // Fungsi groupTasksByRow milik Anda (sudah benar, tapi saya tambahkan sorting agar rapi)
    private function groupTasksByRow($tasks)
    {
        if (empty($tasks)) return [];

        // Urutkan berdasarkan start_col, lalu end_col descending (durasi panjang dulu)
        usort($tasks, function($a, $b) {
            if ($a['start_col'] == $b['start_col']) {
                return $b['end_col'] - $a['end_col'];
            }
            return $a['start_col'] - $b['start_col'];
        });

        $rows = [];
        foreach ($tasks as $task) {
            $placed = false;
            for ($i = 0; $i < count($rows); $i++) {
                $conflict = false;
                foreach ($rows[$i] as $existing) {
                    // Cek tumpang tindih
                    if (!($task['end_col'] < $existing['start_col'] || $task['start_col'] > $existing['end_col'])) {
                        $conflict = true;
                        break;
                    }
                }
                if (!$conflict) {
                    $rows[$i][] = $task;
                    $placed = true;
                    break;
                }
            }
            if (!$placed) {
                $rows[] = [$task];
            }
        }
        return $rows;
    }





}
