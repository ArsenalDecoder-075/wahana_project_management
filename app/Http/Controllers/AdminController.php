<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Branch;
use App\Models\Project;
use App\Models\Task;
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
        // Default week start day to Friday (5)
        $firstDayOfWeek = 5; // Friday

        // Convert numeric day to Carbon constant
        $firstDayConstant = $this->numericDayToCarbon($firstDayOfWeek);

        // Get filter parameters with defaults
        $area = $request->get('area', 'all');
        $city = $request->get('city', 'all');
        $periodType = $request->get('period_type', 'all');

        // Initialize period label based on period type
        $periodLabel = '';

        // Determine date range based on period type
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

        // Store filters for passing to view
        $filters = [
            'period_type' => $periodType,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'area' => $area,
            'city' => $city,
            'period_label' => $periodLabel,
        ];

        // Get user and branch counts
        $totalUserCount = User::count();
        $totalProjectCount = Project::count();
        $totalManagerCount = User::where('type', 2)->count(); // Count of managers
        $totalWorkerCount = User::where('type', 0)->count(); // Count of regular users
        $totalBranchCount = Branch::count();

        // Count users by type
        $adminCount = User::where('type', 1)->count(); // Admin
        $managerCount = User::where('type', 2)->count(); // Manager
        $userCount = User::where('type', 0)->count(); // Regular User

        // Get branch chart data (branches per area)
        $branchChartData = $this->getBranchChartData($area, $city);
        $branchProjectStatusData = $this->getBranchProjectStatusData($area, $city);


        // Get branch overview data
        $branchOverviewData = $this->getBranchOverviewData($area, $city);

        // Get available areas for filter
        $areaLabels = Branch::select('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area')
            ->toArray();

        return view('pages.admin.dashboard.adminHome', compact(
            'filters',
            'totalUserCount',
            'totalProjectCount',
            'totalBranchCount',
            'totalManagerCount',
            'totalWorkerCount',
            'adminCount',
            'managerCount',
            'userCount',
            'branchChartData',
            'branchProjectStatusData',
            'branchOverviewData',
            'areaLabels'
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
            'employee_id' => 'required|exists:users,id',
            'manager_id'  => 'required|exists:users,id',
        ]);

        DB::table('manager_employees')->updateOrInsert(
            ['employee_id' => $request->employee_id], // Kondisi pengecekan (Karyawan)
            ['manager_id' => $request->manager_id, 'updated_at' => now()] // Data yang dimasukkan/diubah
        );

        return response()->json([
            'success' => true,
            'message' => 'Manajer berhasil ditugaskan ke karyawan tersebut!'
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
            $projects = Project::with('manager')->get();

            return datatables()->of($projects)
                ->addIndexColumn()
                ->addColumn('manager', function($row) {
                    return $row->manager ? $row->manager->name : '-';
                })
                ->addColumn('duration', function($row) {
                    return $row->start_date . ' s/d ' . $row->end_date;
                })
                // ->addColumn('progress', function($row) {
                //     // Return rancangan Progress Bar Bootstrap
                //     return '<div class="progress" style="height: 15px;">
                //                 <div class="progress-bar bg-success" role="progressbar" style="width: '.$row->progress.'%" aria-valuenow="'.$row->progress.'" aria-valuemin="0" aria-valuemax="100">'.$row->progress.'%</div>
                //             </div>';
                // })
                // Progress berdasarkan tugas yang selesai vs total tugas
                // ->addColumn('progress', function($row) {
                //     $totalTasks = $row->tasks->count();
                //     $completedTasks = $row->tasks->where('status', 'Completed')->count();

                //     // Hitung progress
                //     if ($totalTasks == 0) {
                //         $progress = 0;
                //         $label = 'Belum ada tugas';
                //     } else {
                //         $progress = round(($completedTasks / $totalTasks) * 100);
                //         $label = $progress . '% (' . $completedTasks . '/' . $totalTasks . ' tugas)';
                //     }

                //     // Tentukan warna berdasarkan progress
                //     if ($progress == 100) {
                //         $color = 'bg-success';
                //     } elseif ($progress >= 50) {
                //         $color = 'bg-primary';
                //     } elseif ($progress >= 25) {
                //         $color = 'bg-warning';
                //     } else {
                //         $color = 'bg-danger';
                //     }

                //     // Update progress di database agar tetap sinkron
                //     if ($row->progress != $progress) {
                //         $row->update(['progress' => $progress]);
                //     }

                //     return '<div class="progress" style="height: 20px; position: relative;">
                //                 <div class="progress-bar ' . $color . ' progress-bar-striped progress-bar-animated"
                //                     role="progressbar"
                //                     style="width: ' . $progress . '%"
                //                     aria-valuenow="' . $progress . '"
                //                     aria-valuemin="0"
                //                     aria-valuemax="100">
                //                     ' . $label . '
                //                 </div>
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
                ->addColumn('action', function($row) {
                    return '
                    <div class="btn-group" role="group">
                        <a href="'.route('admin.tasks.manage', $row->id).'" class="btn btn-info btn-sm">
                            <i class="lni lni-plus"></i> Kelola Tugas
                        </a>
                        <button class="btn btn-warning btn-sm editProjectBtn"
                            data-id="'.$row->id.'"
                            data-title="'.$row->title.'"
                            data-description="'.$row->description.'"
                            data-start="'.$row->start_date.'"
                            data-end="'.$row->end_date.'"
                            data-manager-id="'.$row->manager_id.'"
                            title="Edit Proyek">
                            <i class="lni lni-pencil"></i>
                        </button>
                        <button class="btn btn-danger btn-sm deleteProjectBtn" data-id="'.$row->id.'" data-title="'.$row->title.'" title="Hapus Proyek">
                            <i class="lni lni-trash-can"></i>
                        </button>
                    </div>';
                })
                ->rawColumns(['total_tasks', 'action'])
                ->make(true);
        }

        // Mengambil data user bertipe Employee/Karyawan (asumsi tipe 0 = Karyawan Cabang berdasarkan manageruser.blade.php)
        $employees = User::where('type', '0')->get();
        $managers = User::whereIn('type', ['1', '2'])->orderBy('name')->get();

        return view('pages.admin.manageproject', compact('employees', 'managers'));
    }

    public function storeProject(Request $request)
    {
        // Validasi input form
        $request->validate([
            'manager_id'  => 'required|exists:users,id',
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
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            // 'progress'    => 'required|integer|between:0,100',
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
            'title'       => $request->title,
            'description' => $request->description,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            // 'progress'    => $request->progress,
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
        $project = Project::with('manager')->findOrFail($project_id);

        // Karyawan bawahan manager project
        $employees = User::whereIn('id', function ($query) use ($project) {
            $query->select('employee_id')
                ->from('manager_employees')
                ->where('manager_id', $project->manager_id);
        })->get();

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

        // Hitung jumlah tugas per karyawan untuk proyek ini
        $employeeTaskCounts = Task::where('project_id', $project_id)
        ->select('assigned_to', DB::raw('count(*) as total'))
        ->groupBy('assigned_to')
        ->pluck('total', 'assigned_to')
        ->toArray();

        // Jika AJAX (DataTables)
        if ($request->ajax()) {
            $tasks = Task::with('assignee')
                ->where('project_id', $project_id)
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low') ASC")
                ->orderBy('created_at', 'DESC') // opsional: jika priority sama, urutkan berdasarkan created_at
                ->get();

            return datatables()->of($tasks)
                ->addIndexColumn()
                ->addColumn('title', function($row) {
                    return '<strong>' . $row->title . '</strong>';
                })
                ->addColumn('description', function($row) {
                    if (empty($row->description)) {
                        return '<span class="text-muted"><i class="fas fa-minus"></i> Tidak ada deskripsi</span>';
                    }
                    // Tampilkan seluruh teks dengan word-wrap
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
                        'Revision'    => '<span class="badge bg-danger"><i class="fas fa-undo"></i> Revision</span>'
                    ];
                    return $badges[$row->status] ?? '<span class="badge bg-secondary">' . $row->status . '</span>';
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
                    return '
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-warning editTaskBtn"
                            data-id="'.$row->id.'"
                            data-title="'.addslashes($row->title).'"
                            data-description="'.addslashes($row->description).'"
                            data-priority="'.$row->priority.'"
                            data-assigned="'.$row->assigned_to.'"
                            data-deadline="'.$row->deadline.'"
                            title="Edit Tugas">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger deleteTaskBtn"
                            data-id="'.$row->id.'"
                            data-title="'.addslashes($row->title).'"
                            data-employee="'.($row->assignee?->name ?? 'Tidak Ada').'"
                            data-priority="'.$row->priority.'"
                            data-deadline="'.$row->deadline.'"
                            title="Hapus Tugas">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    ';
                })
                ->rawColumns(['title', 'description', 'employee_name', 'priority_badge', 'status_badge', 'deadline_format', 'action'])
                ->make(true);
        }

        // Kirim data statistik ke view
        return view('pages.admin.managetask', compact(
            'project',
            'employees',
            'priorityStats',
            'totalTasks',
            'employeeTaskCounts',
            'totalCompleted'
        ));
    }

    public function storeTask(Request $request)
    {
        $project = Project::findOrFail($request->project_id);

        $request->validate([
            'project_id'  => 'required|exists:projects,id',
            'assigned_to' => 'required|exists:users,id',
            'title'       => 'required|string|max:255',
            'priority'    => 'required|in:low,medium,high',
            'deadline'    => 'required|date|after_or_equal:' . $project->start_date,
            'description' => 'nullable|string'
        ]);

        Task::create([
            'project_id'   => $request->project_id,
            'assigned_to'  => $request->assigned_to,
            'title'        => $request->title,
            'priority'     => $request->priority,
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
            'deadline'    => 'required|date|after_or_equal:' . $project->start_date,
            'assigned_to' => 'required|exists:users,id',
            'status'      => 'nullable|in:Pending,On Progress,Completed,Revision'
        ]);

        $task->update([
            'title'       => $request->title,
            'description' => $request->description,
            'priority'    => $request->priority,
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
