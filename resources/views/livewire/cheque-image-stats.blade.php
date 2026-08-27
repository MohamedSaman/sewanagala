<div class="cheque-stats-wrapper" style="min-height: 100vh; background: #0f172a; color: #f8fafc; padding: 2rem; font-family: 'Inter', sans-serif;">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            transition: all 0.3s ease;
        }

        .stat-card {
            border-radius: 1.5rem;
            padding: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .gradient-blue { background: linear-gradient(135deg, #16285A 0%, #2563eb 100%); }
        .gradient-purple { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); }
        .gradient-emerald { background: linear-gradient(135deg, #10b981 0%, #047857 100%); }
        .gradient-amber { background: linear-gradient(135deg, #E65F1E 0%, #c2410c 100%); }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .custom-select {
            background: rgba(15, 23, 42, 0.5);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 0.5rem 1rem;
        }

        .custom-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
        }

        .cheque-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>

    <!-- Header Section -->
    <div class="row align-items-center mb-5 animate-fade-in">
        <div class="col">
            <h1 class="fw-bold mb-1" style="font-size: 2.5rem; letter-spacing: -0.025em;">
                Cheque <span style="color: #3b82f6;">Analytics</span>
            </h1>
            <p class="text-muted d-flex align-items-center">
                <span class="badge bg-primary bg-opacity-25 text-primary me-2 px-3 py-2 rounded-pill" style="font-size: 0.7rem;">DEV ACCESS ONLY</span>
                Visualizing document management trends
            </p>
        </div>
        <div class="col-auto">
            <div class="glass-card p-2 d-flex gap-3">
                <div class="d-flex flex-column">
                    <label class="small text-muted ms-1 mb-1">Select Year</label>
                    <select wire:model.live="filterYear" class="custom-select">
                        <option value="">All Years</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex flex-column">
                    <label class="small text-muted ms-1 mb-1">Select Month</label>
                    <select wire:model.live="filterMonth" class="custom-select">
                        <option value="all">All Months</option>
                        @foreach($months as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Stats Grid -->
    <div class="row g-4 mb-5 animate-fade-in" style="animation-delay: 0.1s;">
        <div class="col-md-3">
            <div class="stat-card gradient-blue shadow-lg">
                <div class="d-flex justify-content-between align-items-start">
                    <i class="fas fa-images fa-2x opacity-50"></i>
                    <span class="text-white text-opacity-75 small fw-semibold">TOTAL CHEQUES</span>
                </div>
                <div class="mt-4">
                    <h2 class="display-5 fw-bold mb-0">{{ number_format($stats['total_count']) }}</h2>
                    <p class="mb-0 text-white text-opacity-75 small">All Cheque Records</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card gradient-emerald shadow-lg">
                <div class="d-flex justify-content-between align-items-start">
                    <i class="fas fa-camera fa-2x opacity-50"></i>
                    <span class="text-white text-opacity-75 small fw-semibold">WITH PHOTO</span>
                </div>
                <div class="mt-4">
                    <h2 class="display-5 fw-bold mb-0">{{ number_format($stats['with_photo']) }}</h2>
                    <p class="mb-0 text-white text-opacity-75 small">Photos Uploaded</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card gradient-amber shadow-lg">
                <div class="d-flex justify-content-between align-items-start">
                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    <span class="text-white text-opacity-75 small fw-semibold">WITHOUT PHOTO</span>
                </div>
                <div class="mt-4">
                    <h2 class="display-5 fw-bold mb-0">{{ number_format($stats['without_photo']) }}</h2>
                    <p class="mb-0 text-white text-opacity-75 small">Missing Photos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card gradient-purple shadow-lg">
                <div class="d-flex justify-content-between align-items-start">
                    <i class="fas fa-wallet fa-2x opacity-50"></i>
                    <span class="text-white text-opacity-75 small fw-semibold">VALUE TRACKED</span>
                </div>
                <div class="mt-4">
                    <h2 class="display-5 fw-bold mb-0">Rs.{{ number_format($stats['total_amount'], 0) }}</h2>
                    <p class="mb-0 text-white text-opacity-75 small">Total Cheque Value</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Info Row -->
    <div class="row g-4 animate-fade-in" style="animation-delay: 0.2s;">
        <!-- Latest Activity -->
        <div class="col-lg-8">
            <div class="glass-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Latest Uploaded Document</h5>
                    <a href="{{ route('admin.cheque-list') }}" class="btn btn-sm btn-link text-primary p-0 text-decoration-none fw-semibold">View All Cheques →</a>
                </div>
                
                @if($stats['last_cheque'])
                    <div class="d-flex align-items-center p-3 rounded-4" style="background: rgba(15, 23, 42, 0.4); border: 1px solid rgba(255, 255, 255, 0.05);">
                        <div class="flex-shrink-0 me-4">
                            <div class="rounded-4 overflow-hidden shadow-lg" style="width: 120px; height: 80px; background: #334155;">
                                <img src="{{ $stats['last_cheque']->cheque_photo_url }}" class="w-100 h-100 object-fit-cover" alt="Cheque Preview" onerror="this.src='https://placehold.co/120x80/334155/f8fafc?text=No+Preview'">
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold mb-1">{{ $stats['last_cheque']->bank_name }}</h6>
                                <span class="cheque-badge" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">#{{ $stats['last_cheque']->cheque_number }}</span>
                            </div>
                            <p class="text-muted small mb-2">{{ $stats['last_cheque']->customer->name ?? 'Unknown Customer' }} • {{ \Carbon\Carbon::parse($stats['last_cheque']->created_at)->diffForHumans() }}</p>
                            <div class="d-flex align-items-center gap-3">
                                <span class="fw-bold text-success">Rs.{{ number_format($stats['last_cheque']->cheque_amount, 2) }}</span>
                                <span class="badge rounded-pill bg-{{ $stats['last_cheque']->status == 'complete' ? 'success' : ($stats['last_cheque']->status == 'pending' ? 'warning' : 'danger') }} bg-opacity-25 text-{{ $stats['last_cheque']->status == 'complete' ? 'success' : ($stats['last_cheque']->status == 'pending' ? 'warning' : 'danger') }}" style="font-size: 0.65rem;">{{ strtoupper($stats['last_cheque']->status) }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3 opacity-20"></i>
                        <p class="text-muted">No documents found for this period.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Breakdown Chart Simulation -->
        <div class="col-lg-4">
            <div class="glass-card h-100 p-4">
                <h5 class="fw-bold mb-4 text-center">Status Breakdown</h5>
                <div class="d-flex flex-column gap-3">
                    @php
                        $total = $stats['total_count'] ?: 1;
                        $completeP = ($stats['complete'] / $total) * 100;
                        $pendingP = ($stats['pending'] / $total) * 100;
                        $returnP = ($stats['return'] / $total) * 100;
                    @endphp
                    
                    <div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Completed</span>
                            <span class="fw-bold">{{ number_format($completeP, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 8px; background: rgba(255, 255, 255, 0.05);">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $completeP }}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Pending</span>
                            <span class="fw-bold">{{ number_format($pendingP, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 8px; background: rgba(255, 255, 255, 0.05);">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $pendingP }}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Returned</span>
                            <span class="fw-bold">{{ number_format($returnP, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 8px; background: rgba(255, 255, 255, 0.05);">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $returnP }}%"></div>
                        </div>
                    </div>

                    @php
                        $photoP = $stats['total_count'] > 0 ? ($stats['with_photo'] / $stats['total_count']) * 100 : 0;
                    @endphp
                    <div class="mt-2 pt-3 border-top border-white border-opacity-10">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span><i class="fas fa-camera me-1"></i> Photo Coverage</span>
                            <span class="fw-bold">{{ number_format($photoP, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 8px; background: rgba(255, 255, 255, 0.05);">
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $photoP }}%"></div>
                        </div>
                        <div class="small text-muted mt-1">{{ $stats['with_photo'] }} of {{ $stats['total_count'] }} cheques have photos</div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top border-white border-opacity-10 text-center">
                    <p class="text-muted small mb-0">Filtered Results: <span class="text-white">{{ $stats['total_count'] }} Cheques</span> • <span class="text-info">{{ $stats['with_photo'] }} Photos</span></p>
                </div>
            </div>
        </div>
    </div>
</div>
