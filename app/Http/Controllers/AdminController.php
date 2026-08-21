<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Branch;
use App\Models\Project;
use App\Models\Category;
use App\Models\Task;
use App\Models\TaskReview;
use App\Models\TaskSubmission;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
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
    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function adminHome(Request $request)
    {
        // 1. LOGIKA FILTER TANGGAL (SAMA SEPERTI SEBELUMNYA)
        $firstDayOfWeek = 5; // Friday
        $firstDayConstant = $this->numericDayToCarbon($firstDayOfWeek);

        $area = $request->get('area', 'all');
        $city = $request->get('city', 'all');
        $periodType = $request->get('period_type', 'all');

        $periodLabel = '';
        if ($periodType == 'current_week') {
            $startDate = Carbon::now()->startOfWeek($firstDayConstant)->startOfDay();
            $endDate = (clone $startDate)->addDays(6)->endOfDay();
            $periodLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
        } elseif ($periodType == 'custom' && $request->has('start_date')) {
            $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
            $dayOfWeek = $startDate->dayOfWeek;
            if ($dayOfWeek != $firstDayOfWeek) {
                $startDate = $startDate->previous($firstDayConstant);
            }
            $endDate = (clone $startDate)->addDays(6)->endOfDay();
            $periodLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
        } elseif ($periodType == 'custom' && !$request->has('start_date')) {
            $startDate = Carbon::now()->startOfWeek($firstDayConstant)->startOfDay();
            $endDate = (clone $startDate)->addDays(6)->endOfDay();
            $periodLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
        } else { // 'all'
            $startDate = Carbon::now()->subYear()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            $periodLabel = 'Semua Periode';
        }

        $filters = [
            'period_type' => $periodType,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'area' => $area,
            'city' => $city,
            'period_label' => $periodLabel,
        ];

        // 2. STATISTIK GLOBAL UNTUK ADMIN (Semua Data)
        $totalUserCount = User::count();
        $totalProjectCount = Project::count();
        $totalBranchCount = Branch::count();
        $totalTaskCount = Task::count(); // Tambahan: Total Tugas keseluruhan

        // Statistik Role Pengguna
        $adminCount = User::where('type', 1)->count(); // Admin
        $managerCount = User::where('type', 2)->count(); // Manager
        $userCount = User::where('type', 0)->count(); // Karyawan

        // 3. DATA GRAFIK CABANG
        $branchChartData = $this->getBranchChartData($area, $city);
        $branchProjectStatusData = $this->getBranchProjectStatusData($area, $city);
        $branchOverviewData = $this->getBranchOverviewData($area, $city);

        // Area list untuk filter dropdown
        $areaLabels = Branch::select('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area')
            ->toArray();

        // 4. DATA NOTIFIKASI REVIEW (ADMIN MELIHAT SEMUANYA)
        // Admin tidak terikat manager_id. Lihat semua tugas yang berstatus Review.
        $reviewProjects = [];
        $totalReviewTasks = 0;

        // Ambil semua proyek yang memiliki tugas berstatus Review
        $projectsWithReview = Project::whereHas('tasks', function ($query) {
            $query->where('status', 'Review');
        })->get();

        foreach ($projectsWithReview as $project) {
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

        // 5. DATA STATISTIK KARYAWAN (ADMIN MELIHAT SEMUANYA)
        // Ambil semua karyawan, bukan hanya yang berada di bawah manager tertentu.
        $employees = User::where('type', 0)->get();

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

        // 6. DATA PROGRESS PROYEK (ADMIN MELIHAT SEMUANYA)
        // Ambil semua proyek dari seluruh cabang/manajer.
        $projects = Project::all();
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

        // 7. RETURN VIEW
        return view('pages.admin.dashboard.adminHome', compact(
            'filters',
            'totalUserCount',
            'totalProjectCount',
            'totalBranchCount',
            'totalTaskCount', // Tambahan baru
            'adminCount',
            'managerCount',
            'userCount',
            'branchChartData',
            'branchProjectStatusData',
            'branchOverviewData',
            'areaLabels',
            'reviewProjects',
            'totalReviewTasks',
            'employeeStats',
            'chartLabels',
            'chartCompleted',
            'chartPending',
            'projectProgress'
        ));
    }

    /**
     * Convert numeric day (0-6) to Carbon constant
     */
    private function numericDayToCarbon($day)
    {
        $carbonDays = [
            0 => Carbon::SUNDAY,
            1 => Carbon::MONDAY,
            2 => Carbon::TUESDAY,
            3 => Carbon::WEDNESDAY,
            4 => Carbon::THURSDAY,
            5 => Carbon::FRIDAY,
            6 => Carbon::SATURDAY
        ];

        return $carbonDays[$day] ?? Carbon::FRIDAY;
    }

    /**
     * Get branch distribution data for chart (branches per area)
     */
    private function getBranchChartData($area, $city)
    {
        $query = Branch::query()
            ->when($area && $area !== 'all', function ($query) use ($area) {
                return $query->where('area', $area);
            })
            ->when($city && $city !== 'all', function ($query) use ($city) {
                return $query->where('city', $city);
            })
            ->select('area', DB::raw('count(*) as total'))
            ->groupBy('area')
            ->orderBy('area')
            ->get();

        $labels = [];
        $data = [];

        foreach ($query as $item) {
            $labels[] = $item->area;
            $data[] = $item->total;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * Get branch project status data for chart (projects per branch by status)
     * Menampilkan jumlah proyek per cabang berdasarkan status (selesai vs belum selesai)
     */
    private function getBranchProjectStatusData($area, $city)
    {
        // Ambil semua branch dengan filter
        $branches = Branch::query()
            ->when($area && $area !== 'all', function ($query) use ($area) {
                return $query->where('area', $area);
            })
            ->when($city && $city !== 'all', function ($query) use ($city) {
                return $query->where('city', $city);
            })
            ->orderBy('name')
            ->get();

        $labels = [];
        $completedData = []; // Proyek selesai (Completed)
        $pendingData = []; // Proyek belum selesai (Pending, On Progress)

        foreach ($branches as $branch) {
            $labels[] = $branch->name;

            // Ambil semua manager di branch ini
            $managerIds = User::where('branch_id', $branch->id)
                ->where('type', 2) // Type 2 = Manager
                ->pluck('id')
                ->toArray();

            // Hitung proyek yang sudah selesai (status = 'Completed')
            $completedCount = Project::whereIn('manager_id', $managerIds)
                ->where('status', 'Completed')
                ->count();

            // Hitung proyek yang belum selesai (status != 'Completed')
            $pendingCount = Project::whereIn('manager_id', $managerIds)
                ->where('status', '!=', 'Completed')
                ->count();

            $completedData[] = $completedCount;
            $pendingData[] = $pendingCount;
        }

        // Jika tidak ada data, berikan default
        if (empty($labels)) {
            $labels = ['Tidak Ada Data'];
            $completedData = [0];
            $pendingData = [0];
        }

        return [
            'labels' => $labels,
            'completed' => $completedData, // Proyek selesai
            'pending' => $pendingData,     // Proyek belum selesai
        ];
    }

    /**
     * Get branch overview data with user counts
     */
    private function getBranchOverviewData($area, $city)
    {
        $branchesQuery = Branch::query()
            ->when($area && $area !== 'all', function ($query) use ($area) {
                return $query->where('area', $area);
            })
            ->when($city && $city !== 'all', function ($query) use ($city) {
                return $query->where('city', $city);
            })
            ->orderBy('area')
            ->orderBy('name');

        $branches = $branchesQuery->get();
        $result = [];

        foreach ($branches as $branch) {
            // Count users by type for this branch
            $totalUsers = User::where('branch_id', $branch->id)->count();
            $adminCount = User::where('branch_id', $branch->id)->where('type', 1)->count();
            $managerCount = User::where('branch_id', $branch->id)->where('type', 2)->count();
            $userCount = User::where('branch_id', $branch->id)->where('type', 0)->count();

            $result[] = [
                'area' => $branch->area,
                'name' => $branch->name,
                'initials' => $branch->initials,
                'city' => $branch->city,
                'category' => $branch->category,
                'totalUsers' => $totalUsers,
                'adminCount' => $adminCount,
                'managerCount' => $managerCount,
                'userCount' => $userCount,
            ];
        }

        return $result;
    }
    /*------------------------------------------
    --------------------------------------------
    Branch Management
    --------------------------------------------
    --------------------------------------------*/
    public function adminBranch()
    {
        return view('pages.admin.managebranch');
    }

    // Data untuk DataTables
    public function manageBranch(Request $request)
    {
        if ($request->ajax()) {
            $data = Branch::select(['id', 'area', 'city', 'name', 'initials', 'category', 'address']);
            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }
        return view('pages.admin.managebranch');
    }

    // Store branch baru
    public function storeBranch(Request $request)
    {
        $request->validate([
            'area' => 'required',
            'city' => 'required',
            'name' => 'required|unique:branches,name',
            'initials' => 'required|unique:branches,initials',
            'category' => 'required|in:H1,H23,H123',
            'address' => 'nullable',
        ], [
            'name.unique' => 'Nama cabang sudah digunakan',
            'initials.unique' => 'Inisial sudah digunakan',
        ]);

        Branch::create([
            'area' => $request->area,
            'city' => $request->city,
            'name' => $request->name,
            'initials' => $request->initials,
            'category' => $request->category,
            'address' => $request->address,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Cabang berhasil ditambahkan!']);
        }

        return redirect()->back()->with('success', 'Cabang berhasil ditambahkan!');
    }

    // Edit branch
    public function editBranch(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:branches,id',
            'area' => 'required',
            'city' => 'required',
            'name' => 'required|unique:branches,name,' . $request->id,
            'initials' => 'required|unique:branches,initials,' . $request->id,
            'category' => 'required|in:H1,H23,H123',
            'address' => 'nullable',
        ], [
            'name.unique' => 'Nama cabang sudah digunakan',
            'initials.unique' => 'Inisial sudah digunakan',
        ]);

        $branch = Branch::findOrFail($request->id);
        $branch->update([
            'area' => $request->area,
            'city' => $request->city,
            'name' => $request->name,
            'initials' => $request->initials,
            'category' => $request->category,
            'address' => $request->address,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Cabang berhasil diupdate!']);
        }

        return redirect()->back()->with('success', 'Cabang berhasil diupdate!');
    }

    // Hapus branch
    public function deleteBranch(Request $request)
    {
        $branch = Branch::findOrFail($request->id);
        $branch->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Cabang berhasil dihapus!']);
        }

        return redirect()->back()->with('success', 'Cabang berhasil dihapus!');
    }
    /*------------------------------------------
    --------------------------------------------
    User Management Methods (Simplified)
    --------------------------------------------*/
    public function adminUser(Request $request)
    {
        if ($request->ajax()) {
            $users = User::with(['branch'])
                ->whereIn('type', [0, 2])
                ->orderByRaw('type DESC')
                ->latest('created_at');

            return datatables()->of($users)
                ->addIndexColumn()
                ->addColumn('branch', fn($user) => $user->branch ? $user->branch->name : 'HO')
                ->addColumn('action', function ($user) {
                    $btn = '<div class="btn-group" role="group">';
                    $btn .= '<button type="button" class="btn btn-sm btn-primary me-1 rounded editBtn"
                        data-id="' . $user->id . '"
                        data-name="' . htmlspecialchars($user->name, ENT_QUOTES) . '"
                        data-email="' . htmlspecialchars($user->email, ENT_QUOTES) . '"
                        data-branch="' . $user->branch_id . '"
                        data-type="' . $user->type . '"
                        title="Edit">
                        <i class="fas fa-edit"></i></button>';

                    $btn .= '<button type="button" class="btn btn-sm btn-warning me-1 rounded resetPasswordBtn"
                        data-id="' . $user->id . '"
                        title="Reset Password">
                        <i class="fa fa-key"></i></button>';

                    $btn .= '<button type="button" class="btn btn-sm btn-danger me-1 rounded deleteBtn"
                        data-id="' . $user->id . '"
                        title="Hapus">
                        <i class="fa-solid fa-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $branches = Branch::all();
        return view('pages.admin.manageruser', compact('branches'));
    }

    public function addUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|unique:users,email',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|in:0,2',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('wari321'),
            'type' => $request->type,
            'branch_id' => $request->type == 0 ? $request->branch_id : null,
        ];

        User::create($userData);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'User berhasil ditambahkan!']);
        }
        return redirect()->back()->with('success', 'User berhasil ditambahkan!');
    }

    public function updateUser(Request $request)
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|string|unique:users,email,' . $request->user_id,
            'type' => 'required|in:0,2',
        ];

        if ($request->type == '0') {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $request->validate($rules);

        $user = User::findOrFail($request->user_id);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'branch_id' => $request->type == 0 ? $request->branch_id : null,
            'type' => $request->type,
        ];

        $user->update($updateData);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'User berhasil diperbarui!']);
        }
        return redirect()->route('admin.user')->with('success', 'User berhasil diperbarui!');
    }

    public function resetUserPassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $newPassword = Str::random(6);
        $user->update(['password' => Hash::make($newPassword)]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset. Password baru: ' . $newPassword
            ]);
        }
        return redirect()->route('admin.user')->with('success', 'Password berhasil direset. Password baru: ' . $newPassword);
    }

    public function deleteUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        User::findOrFail($request->user_id)->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'User berhasil dihapus!']);
        }
        return redirect()->back()->with('success', 'User berhasil dihapus!');
    }

    /*------------------------------------------
    --------------------------------------------
    Category Project
    --------------------------------------------*/
    // Halaman Category Project dengan Data untuk DataTables
    public function manageCategory(Request $request)
    {
        if ($request->ajax()) {
            $data = Category::select(['id', 'name', 'description', 'is_active', 'created_at']);
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function($row) {
                    $badge = $row->is_active ? 'success' : 'danger';
                    $text = $row->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('action', function($row) {
                    $editBtn = '<button class="btn btn-sm btn-primary edit-btn"
                                data-id="' . $row->id . '"
                                data-name="' . htmlspecialchars($row->name, ENT_QUOTES) . '"
                                data-description="' . htmlspecialchars($row->description ?? '', ENT_QUOTES) . '"
                                data-is_active="' . $row->is_active . '">
                                <i class="fas fa-edit"></i> Edit
                            </button>';

                    $deleteBtn = '<button class="btn btn-sm btn-danger delete-btn"
                                data-id="' . $row->id . '"
                                data-name="' . htmlspecialchars($row->name, ENT_QUOTES) . '">
                                <i class="fas fa-trash"></i> Delete
                            </button>';

                    return $editBtn . ' ' . $deleteBtn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('pages.admin.managecategories');
    }

    // Simpan category
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ], [
            'name.unique' => 'Nama kategori sudah digunakan',
            'name.required' => 'Nama kategori wajib diisi',
        ]);

        $isActive = false;
        if ($request->has('is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }

        Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $isActive,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil ditambahkan!'
            ]);
        }

        return redirect()->back()->with('success', 'Kategori berhasil ditambahkan!');
    }

    // Edit Category
    public function editCategory(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255|unique:categories,name,' . $request->id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ], [
            'name.unique' => 'Nama kategori sudah digunakan',
            'name.required' => 'Nama kategori wajib diisi',
        ]);

        $category = Category::findOrFail($request->id);
        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diupdate!'
            ]);
        }

        return redirect()->back()->with('success', 'Kategori berhasil diupdate!');
    }

    public function deleteCategory(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:categories,id'
        ]);

        $category = Category::findOrFail($request->id);

        // Cek apakah kategori masih digunakan di projects
        if ($category->projects()->count() > 0) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh ' . $category->projects()->count() . ' project(s)!'
                ], 400);
            }
            return redirect()->back()->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh project!');
        }

        $category->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil dihapus!'
            ]);
        }

        return redirect()->back()->with('success', 'Kategori berhasil dihapus!');
    }

    /**
     * Get category data for edit (AJAX)
     */
    public function getCategory(Request $request)
    {
        $category = Category::findOrFail($request->id);
        return response()->json($category);
    }

    /**
     * Toggle status category (Active/Inactive)
     */
    public function toggleStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:categories,id'
        ]);

        $category = Category::findOrFail($request->id);
        $category->is_active = !$category->is_active;
        $category->save();

        $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil ' . $status . '!',
                'is_active' => $category->is_active
            ]);
        }

        return redirect()->back()->with('success', 'Kategori berhasil ' . $status . '!');
    }

    /*------------------------------------------
    --------------------------------------------
    Profile Admin
    --------------------------------------------*/
    public function adminProfile()
    {
        $user = User::find(Auth::id());

        return view('pages.admin.adminprofile', compact('user'));
    }

    /*------------------------------------------
    --------------------------------------------
    Hierarki Management
    --------------------------------------------
    --------------------------------------------*/

    public function manageHierarchy(Request $request)
    {
        if ($request->ajax()) {
            $managers = User::whereIn('type', ['1', '2'])
                ->with(['branch'])
                ->orderBy('name')
                ->get();

            $data = [];
            foreach ($managers as $manager) {
                $employees = DB::table('manager_employees')
                    ->join('users as employees', 'manager_employees.employee_id', '=', 'employees.id')
                    ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
                    ->where('manager_employees.manager_id', $manager->id)
                    ->select('employees.id', 'employees.name', 'branches.name as branch_name')
                    ->orderBy('employees.name')
                    ->get();

                // Kumpulkan cabang unik
                $branchNames = [];
                foreach ($employees as $emp) {
                    if ($emp->branch_name && !in_array($emp->branch_name, $branchNames)) {
                        $branchNames[] = $emp->branch_name;
                    }
                }
                $branchDisplay = !empty($branchNames) ? implode(', ', $branchNames) : '-';

                $employeeList = [];
                if ($employees->isEmpty()) {
                    $employeeList[] = [
                        'id' => null,
                        'name' => 'Belum Ada',
                        'branch_name' => '-'
                    ];
                    $hasEmployees = false;
                } else {
                    foreach ($employees as $emp) {
                        $employeeList[] = [
                            'id' => $emp->id,
                            'name' => $emp->name,
                            'branch_name' => $emp->branch_name ?? '-'
                        ];
                    }
                    $hasEmployees = true;
                }

                $data[] = [
                    'manager_id'      => $manager->id,
                    'manager_name'    => $manager->name,
                    'branch_display'  => $branchDisplay,
                    'employees'       => $employeeList,
                    'has_employees'   => $hasEmployees,
                ];
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('manager_display', function($row) {
                    return '<strong>' . $row['manager_name'] . '</strong>';
                })
                ->addColumn('employee_display', function($row) {
                    $html = '<div>';
                    foreach ($row['employees'] as $emp) {
                        $html .= '<div class="d-flex justify-content-between align-items-center py-1 border-bottom">';
                        $html .= '<span>' . $emp['name'] . '</span>';
                        if ($emp['id']) {
                            $html .= '<button class="btn btn-sm btn-danger unassignBtn ms-2"
                                        data-id="'.$emp['id'].'"
                                        data-name="'.$emp['name'].'"
                                        title="Cabut Manajer">
                                        Hapus
                                    </button>';
                        } else {
                            $html .= '<span class="text-muted">-</span>';
                        }
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('action', function($row) {
                    return '<button class="btn btn-sm btn-success addEmployeeBtn"
                                data-manager-id="'.$row['manager_id'].'"
                                data-manager-name="'.$row['manager_name'].'">
                                <i class="fas fa-plus"></i> Tambah
                            </button>';
                })
                ->rawColumns(['manager_display', 'employee_display', 'action'])
                ->make(true);
        }

        // Data untuk dropdown
        $employees = User::with('branch')
            ->where('type', '0')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('manager_employees')
                    ->whereColumn('manager_employees.employee_id', 'users.id');
            })
            ->orderBy('name')
            ->get();

        $managers = User::whereIn('type', ['1', '2'])->orderBy('name')->get();

        return view('pages.admin.managehierarchy', compact('employees', 'managers'));
    }
    public function storeHierarchy(Request $request)
    {
        $request->validate([
            'manager_id' => 'required|exists:users,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:users,id',
        ]);

        $managerId = $request->manager_id;
        $employeeIds = $request->input('employee_ids', []);

        $existing = DB::table('manager_employees')
            ->whereIn('employee_id', $employeeIds)
            ->where('manager_id', $managerId)
            ->pluck('employee_id')
            ->toArray();

        $newIds = array_diff($employeeIds, $existing);

        $now = now();
        $insertData = [];
        foreach ($newIds as $empId) {
            $insertData[] = [
                'employee_id' => $empId,
                'manager_id' => $managerId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($insertData)) {
            DB::table('manager_employees')->insert($insertData);
        }

        $count = count($newIds);
        $message = $count > 0
            ? "{$count} karyawan berhasil ditugaskan ke manajer tersebut!"
            : 'Semua karyawan yang dipilih sudah berada di bawah manajer tersebut.';

        return response()->json([
            'success' => $count > 0,
            'message' => $message
        ]);
    }

    public function updateHierarchy(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'manager_id'  => 'required|exists:users,id',
        ]);

        // update manager_id berdasarkan employee_id di tabel manager_employees
        DB::table('manager_employees')
            ->where('employee_id', $request->employee_id)
            ->update([
                'manager_id' => $request->manager_id,
                'updated_at' => now()
            ]);

        // 3. Kembalikan response JSON untuk AJAX
        return response()->json([
            'success' => true,
            'message' => 'Manajer karyawan berhasil diperbarui!'
        ]);
    }

    public function unassignHierarchy(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
        ]);

        // Hapus relasi karyawan tersebut dari tabel manager_employees
        DB::table('manager_employees')
            ->where('employee_id', $request->employee_id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Manajer berhasil dicabut. Karyawan kini belum memiliki atasan.'
        ]);
    }

    /*------------------------------------------
    --------------------------------------------
    Project Management
    --------------------------------------------
    --------------------------------------------*/

    public function manageProject(Request $request)
    {
        if ($request->ajax()) {
            // $projects = Project::with('manager')->get();
            $projects = Project::with(['manager', 'category'])->get();

            return datatables()->of($projects)
                ->addIndexColumn()
                ->addColumn('manager', function($row) {
                    return $row->manager ? $row->manager->name : '-';
                })
                ->addColumn('category_name', function($row) {
                    return '<span class="fw-bold">' . ($row->category?->name ?? 'Tidak Ada') . '</span>';
                })
                // ->addColumn('title', function($row) {
                //     $title = '<div class="fw-bold text-dark mb-1">' . e($row->title) . '</div>';
                //     $description = '<div class="text-muted small">' . e($row->description) . '</div>';
                //     return $title . $description;
                // })
                // ->addColumn('duration', function($row) {
                //     return $row->start_date . ' s/d ' . $row->end_date;
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
                ->addColumn('total_tasks', function($row) {
                    $total = $row->tasks->count();
                    $completed = $row->tasks->where('status', 'Completed')->count();

                    if ($total == 0) {
                        return '<span class="text-muted"><i class="fas fa-minus"></i> 0 tugas</span>';
                    }

                    // return '<span class="fw-bold">Jumlah Tugas : ' . $total . '</span>
                    //         <span class="text-muted"> | Tugas Selesai: ' . $completed . '</span>';
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
                ->addColumn('action', function($row) {
                    return '
                    <div class="btn-group" role="group">
                        <a href="'.route('admin.tasks.manage', $row->id).'" class="btn btn-info btn-sm" title="Kelola Tugas Proyekk">
                            <i class="lni lni-plus"></i>
                        </a>
                        <button class="btn btn-warning btn-sm editProjectBtn"
                            data-id="'.$row->id.'"
                            data-title="'.htmlspecialchars($row->title ?? '', ENT_QUOTES, 'UTF-8').'"
                            data-description="'.htmlspecialchars($row->description ?? '', ENT_QUOTES, 'UTF-8').'"
                            data-start="'.$row->start_date.'"
                            data-end="'.$row->end_date.'"
                            data-manager-id="'.$row->manager_id.'"
                            data-category-id="'.$row->category_id.'"
                            data-status="'.$row->status.'"
                            title="Edit Proyek">
                            <i class="lni lni-pencil"></i>
                        </button>
                        <button class="btn btn-danger btn-sm deleteProjectBtn" data-id="'.$row->id.'" data-title="'.$row->title.'" title="Hapus Proyek">
                            <i class="lni lni-trash-can"></i>
                        </button>
                    </div>';
                })
                ->rawColumns(['title', 'duration', 'total_tasks', 'category_name', 'action']) // ⭐ Tambahkan 'title' di sini
                ->make(true);
        }

        // Mengambil data user bertipe Employee/Karyawan (0 = Karyawan Cabang)
        $employees = User::where('type', '0')->get();
        $managers = User::whereIn('type', ['1', '2'])->orderBy('name')->get();
        $categories = Category::where('is_active', true)->get(); // ✅ Gunakan nama yang tepat

        return view('pages.admin.manageproject', compact('employees', 'managers', 'categories'));
    }

    public function storeProject(Request $request)
    {
        // Validasi input form
        $request->validate([
            'manager_id'  => 'required|exists:users,id',
            'category_id'  => 'required|exists:categories,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
        ]);

        // PROTEKSI: Cek apakah manager_id yang dipilih benar-benar Admin (1) atau Manager (2)
        $managerUser = User::find($request->manager_id);
        if (!in_array($managerUser->type, ['1', '2'])) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Gagal: Penanggung jawab harus seorang Admin atau Manager!'
            ], 422);
        }

        // Simpan ke database menggunakan Eloquent Proyek
        Project::create([
            'manager_id'  => $request->manager_id,
            'category_id'  => $request->category_id,
            'title'       => $request->title,
            'description' => $request->description,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'progress'    => 0, // Nilai default bawaan
            'status'      => 'Pending', // Nilai default bawaan
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proyek baru berhasil dibuat!'
        ]);
    }

    public function updateProject(Request $request)
    {
        // Validasi input form (membutuhkan project_id untuk pencarian data)
        $request->validate([
            'project_id'  => 'required|exists:projects,id',
            'manager_id'  => 'required|exists:users,id',
            'category_id'  => 'required|exists:categories,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'status'      => 'required|in:Pending,On Progress,Completed',
        ]);

        // PROTEKSI: Cek apakah manager_id yang ditunjuk benar-benar Admin (1) atau Manager (2)
        $managerUser = User::find($request->manager_id);
        if (!in_array($managerUser->type, ['1', '2'])) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Gagal: Penanggung jawab harus seorang Admin atau Manager!'
            ], 422);
        }

        // Cari proyek dan update datanya
        $project = Project::findOrFail($request->project_id);
        $project->update([
            'manager_id'  => $request->manager_id,
            'category_id'  => $request->category_id,
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
        // Validasi input berupa ID Proyek
        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        // Temukan proyek lalu hapus
        // Karena migration menggunakan onDelete('cascade'), seluruh Tugas (Tasks)
        // yang terikat dengan proyek ini otomatis akan ikut terhapus di database.
        $project = Project::findOrFail($request->project_id);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proyek beserta tugas di dalamnya berhasil dihapus!'
        ]);
    }

    public function manageTasks(Request $request, $project_id)
    {
        $project = Project::with(['manager', 'category'])->findOrFail($project_id);

        // Karyawan bawahan manager project
        $employees = User::whereIn('id', function ($query) use ($project) {
            $query->select('employee_id')
                ->from('manager_employees')
                ->where('manager_id', $project->manager_id);
        })->get();

        // Ambil semua tugas proyek untuk statistik (hanya untuk view, bukan DataTables)
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

        // Hitung jumlah tugas per karyawan untuk proyek ini
        $employeeTaskCounts = Task::where('project_id', $project_id)
            ->select('assigned_to', DB::raw('count(*) as total'))
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to')
            ->toArray();

        // ✅ AJAX untuk Kanban (ambil semua tugas tanpa paginasi, field mentah)
        if ($request->ajax() && $request->has('kanban')) {
            $tasks = Task::with('assignee')
                ->where('project_id', $project_id)
                ->get();

            return response()->json($tasks->map(function ($task) {
                return [
                    'id'            => $task->id,
                    'title'         => $task->title,
                    'status'        => $task->status,
                    'priority'      => $task->priority,
                    'employee_name' => $task->assignee ? $task->assignee->name : 'Tidak Ada',
                ];
            }));
        }

        // ✅ AJAX untuk DataTables
        if ($request->ajax()) {
            $tasks = Task::with(['assignee'])
            ->withCount([
                // Hitung submission yang pending (menunggu review)
                'submissions as pending_submissions' => function($query) {
                    $query->where('status', 'pending');
                },
                // Hitung submission yang sudah direview
                'submissions as reviewed_submissions' => function($query) {
                    $query->whereIn('status', ['reviewed', 'rejected']);
                }
            ])
            ->where('project_id', $project_id)
            ->select('tasks.*');

            return datatables()->of($tasks)
                ->addIndexColumn()
                ->addColumn('title', function($row) {
                    return '<strong>' . $row->title . '</strong>';
                })
                ->addColumn('description', function($row) {
                    if (empty($row->description)) {
                        return '<span class="text-muted"><i class="fas fa-minus"></i> Tidak ada deskripsi</span>';
                    }
                    return '<div style="word-wrap: break-word; white-space: normal;">'
                            . nl2br(e($row->description)) . '</div>';
                })
                ->addColumn('employee_name', function($row) {
                    return $row->assignee
                        ? '<span class="badge bg-primary"><i class="fas fa-user"></i> ' . $row->assignee->name . '</span>'
                        : '<span class="badge bg-secondary">Tidak Ada</span>';
                })
                ->addColumn('priority_badge', function($row) {
                    $badges = [
                        'high'   => '<span class="badge bg-danger"><i class="fas fa-arrow-up"></i> HIGH</span>',
                        'medium' => '<span class="badge bg-warning text-dark"><i class="fas fa-minus"></i> MEDIUM</span>',
                        'low'    => '<span class="badge bg-info"><i class="fas fa-arrow-down"></i> LOW</span>'
                    ];
                    return $badges[$row->priority] ?? '<span class="badge bg-secondary">UNKNOWN</span>';
                })
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
                ->addColumn('startdate_format', function ($row) {
                    if (empty($row->start_date)) {
                        return '-';
                    }
                    return \Carbon\Carbon::parse($row->start_date)->format('d M Y');
                })
                ->addColumn('deadline_format', function ($row) {
                    $deadline = Carbon::parse($row->deadline);
                    $now = Carbon::now();

                    if ($deadline->lt($now) && $row->status != 'Completed') {
                        return '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> '
                            . $deadline->format('d M Y') . ' <span class="badge bg-danger">OVERDUE</span></span>';
                    }
                    if ($deadline->diffInDays($now) <= 3 && $deadline->gt($now) && $row->status != 'Completed') {
                        return '<span><i class="fas fa-clock"></i> '
                            . $deadline->format('d M Y') . ' <span class="badge bg-warning">Soon</span></span>';
                    }
                    return '<span class="text-success"><i class="fas fa-calendar-alt"></i> '
                        . $deadline->format('d M Y') . '</span>';
                })

                ->addColumn('action', function($row) {
                    $route = 'admin.tasks.review';
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
                ->rawColumns(['title', 'description', 'employee_name', 'priority_badge', 'status_badge', 'deadline_format',
                'submission_status',
                'action'])
                ->make(true);
        }

        // Kirim data statistik ke view (non-AJAX)
        return view('pages.admin.managetask', compact(
            'project',
            'employees',
            'priorityStats',
            'totalTasks',
            'employeeTaskCounts',
            'totalCompleted'
        ));
    }

    public function kanbanView(Request $request)
    {
        // Admin dapat mengakses semua proyek
        $projects = Project::with(['manager', 'category'])
            ->orderBy('title')
            ->get();

        return view('pages.admin.kanban', compact('projects'));
    }

    public function kanbanTasks(Request $request)
    {
        $request->validate([
            'project_ids' => 'required|array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        $tasks = Task::with(['assignee', 'project'])
            ->whereIn('project_id', $request->project_ids)
            ->get();

        $tasksData = $tasks->map(function ($task) {
            return [
                'id'           => $task->id,
                'title'        => $task->title,
                'description'  => $task->description,
                'status'       => $task->status,
                'priority'     => $task->priority,
                'employee_name'=> $task->assignee ? $task->assignee->name : 'Tidak Ada',
                'project_id'   => $task->project_id,
                'project_name' => $task->project ? $task->project->title : '-',
                'duration'     => $task->start_date && $task->deadline
                    ? Carbon::parse($task->start_date)->diffInDays(Carbon::parse($task->deadline)) + 1 . ' hari'
                    : '-',
            ];
        });

        return response()->json($tasksData);
    }

    public function storeTask(Request $request)
    {
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
            // 'deadline'    => 'required|date|after_or_equal:' . $project->start_date,
            'description' => 'nullable|string'
        ]);

        Task::create([
            'project_id'   => $request->project_id,
            'assigned_to'  => $request->assigned_to,
            'title'        => $request->title,
            'priority'     => $request->priority,
            'start_date'  => $request->startdate,
            'deadline'     => $request->deadline,
            'description'  => $request->description,
            'status'       => 'Pending',
            'created_by'   => Auth::id()
        ]);

        return response()->json([
            'message' => 'Tugas berhasil ditambahkan!'
        ]);
    }

    public function updateTask(Request $request)
    {
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
            // 'deadline'    => 'required|date|after_or_equal:' . $project->start_date,
            'assigned_to' => 'required|exists:users,id',
            'status'      => 'nullable|in:Pending,On Progress,Completed,Revision'
        ]);

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

    public function deleteTask(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:tasks,id'
        ]);

        $task = Task::findOrFail($request->id);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tugas "' . $task->title . '" berhasil dihapus!'
        ]);
    }

    /**
     * Halaman Review Submission untuk tugas tertentu
     */
    public function taskReview($project_id, $task_id)
    {
        $userId = Auth::id();

        // Ambil project dan task
        $project = Project::with('manager')->findOrFail($project_id);
        $task = Task::with(['assignee', 'creator'])->findOrFail($task_id);

        // Ambil SEMUA submission untuk tugas ini (urut dari yang terbaru)
        $submissions = $task->submissions()
            ->with(['employee', 'reviews.reviewer', 'files']) // tambahkan 'files'
            ->where('task_id', $task_id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Ambil submission yang pending (untuk ditampilkan di card utama)
        $pendingSubmission = $submissions->where('status', 'pending')->first();

        return view('pages.admin.submissions.review', compact(
            'project',
            'task',
            'submissions',
            'pendingSubmission'
        ));
    }

    /**
     * Proses review submission
     */
    public function reviewSubmission(Request $request)
    {
        $request->validate([
            'submission_id' => 'required|exists:task_submissions,id',
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $userId = Auth::id();
        $userType = Auth::user()->type;
        $submission = TaskSubmission::with(['task', 'task.project'])->findOrFail($request->submission_id);

        // Validasi akses
        if ($userType == 2 && $submission->task->project->manager_id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke submission ini!'
            ], 403);
        }

        // Cek apakah submission masih pending
        if ($submission->status != 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Submission ini sudah direview sebelumnya.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update submission
            $submission->update([
                'status' => $request->status == 'approved' ? 'reviewed' : 'rejected',
                'review_notes' => $request->review_notes,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            // Jika disetujui, update status tugas menjadi Completed
            if ($request->status == 'approved') {
                $submission->task->update([
                    'status' => 'Completed'
                ]);

                // Update progress project
                $this->updateProjectProgress($submission->task->project_id);
            }

            DB::commit();

            $route = $userType == 1 ? 'admin.tasks.manage' : 'manager.tasks.manage';

            return response()->json([
                'success' => true,
                'message' => $request->status == 'approved'
                    ? '✅ Submission disetujui! Tugas selesai.'
                    : '❌ Submission ditolak.',
                'redirect_url' => route($route, $submission->task->project_id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update progress project
     */
    // private function updateProjectProgress($projectId)
    // {
    //     $tasks = Task::where('project_id', $projectId)->get();
    //     $totalWeight = $tasks->sum('weight');
    //     $completedWeight = $tasks->where('status', 'Completed')->sum('weight');

    //     $progress = $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100) : 0;

    //     Project::where('id', $projectId)->update(['progress' => $progress]);
    // }
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

        $user = Auth::user();
        $task = Task::with('project')->findOrFail($request->task_id);

        if ($user->type == 0) { // Blokir Karyawan
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak memiliki akses untuk mengubah status tugas.'
            ], 403);
        }

        if ($user->type == 2) { // Cek Manager
            if ($task->project->manager_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke proyek tugas ini!'
                ], 403);
            }
        }

        $task->update(['status' => $request->status]);

        // Update progress proyek
        $this->updateProjectProgress($task->project_id);

        return response()->json([
            'success' => true,
            'message' => 'Status tugas berhasil diperbarui menjadi ' . $request->status
        ]);
    }

    public function updateReview(Request $request)
    {
        $request->validate([
            'submission_id' => 'required|exists:task_submissions,id',
            'review_notes' => 'nullable|string|max:1000'
        ]);

        $user = Auth::user();
        $submission = TaskSubmission::with('task.project')->findOrFail($request->submission_id);
        $review = $submission->review;

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review tidak ditemukan untuk submission ini.'
            ], 404);
        }

        if ($user->type == 0) { // Blokir Karyawan
            return response()->json([
                'success' => false,
                'message' => 'Anda (Karyawan) tidak memiliki akses untuk mengubah feedback.'
            ], 403);
        }

        if ($user->type == 2) { // Cek Manager
            if ($submission->task->project->manager_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke submission ini!'
                ], 403);
            }
        }
        // Jika user type == 1 (Admin), maka langsung diizinkan tanpa pengecekan.

        // (Pastikan kolom di database bernama 'notes' atau sesuaikan)
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
            'status' => 'required|in:accepted,rejected' // Validasi status
        ]);

        $user = Auth::user();
        $userId = $user->id;
        $submission = TaskSubmission::with('task.project')->findOrFail($request->submission_id);

        // ===========================================
        // PERBAIKAN LOGIKA AKSES (Hak Akses)
        // Asumsi Role: 0 = Karyawan, 1 = Admin, 2 = Manager
        // ===========================================

        // 1. Blokir Karyawan (type 0)
        if ($user->type == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Anda (Karyawan) tidak memiliki hak akses untuk memberikan feedback.'
            ], 403);
        }

        // 2. Cek Akses Khusus Manager (type 2)
        // Jika Admin (type 1), logika ini tidak akan dijalankan, sehingga Admin BISA komen di mana pun!
        if ($user->type == 2 && $submission->task->project->manager_id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke submission ini!'
            ], 403);
        }
        // ===========================================
        // SELESAI PERBAIKAN
        // ===========================================

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
            'manager_id' => $userId, // Kolom ini disimpan user_id (bisa Admin atau Manager)
            'feedback_notes' => $request->feedback_notes,
            'status' => $request->status, // Gunakan status dari request
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->status == 'approved'
                ? '✅ Feedback berhasil ditambahkan! Submission disetujui.'
                : '❌ Feedback berhasil ditambahkan! Submission ditolak.'
        ]);
    }

    // Halaman kalender
    public function calendar(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        return view('pages.admin.calendar', compact('year', 'month'));
    }

    // Ambil data buat halaman calender
    public function calendarData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));

        // Admin melihat semua proyek
        $projects = Project::pluck('id');

        // Tentukan rentang bulan yang sedang dilihat
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // Ambil tugas yang overlap dengan bulan ini
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
                    'title' => $task->title,
                    'project_id' => $task->project_id,
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

    // Ini yg biasa, jgn ganti
    public function adminChangePassword()
    {
        return view('pages.admin.change_password');
    }


    public function adminUpdatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Retrieve users based on the ID that is currently logged in
        $user = User::find(Auth::id());

        if (!$user) {
            return redirect()->route('admin.changePassword')->with('error', 'User tidak ditemukan');
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

        return redirect()->route('admin.changePassword')->with('error', 'Kata sandi lama salah.');
    }
}
