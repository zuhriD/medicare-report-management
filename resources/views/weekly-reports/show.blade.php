<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Report - Week {{ $weeklyReport->week_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                size: A4 portrait;
                /* margin: 20mm; */
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background-color: white !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 p-8 font-sans print:bg-white print:p-0">
    <div class="max-w-5xl mx-auto bg-white p-10 rounded-lg shadow-lg print:shadow-none print:w-full print:max-w-full print:p-0 print:m-0">

        <div class="flex justify-end mb-4 print:hidden">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow flex items-center transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Report
            </button>
        </div>

        <div class="border-b pb-6 mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-800">Weekly Report - Week {{ $weeklyReport->week_number }}</h1>
            <p class="text-gray-500 mt-2 text-base">{{ $weeklyReport->start_date->format('M d, Y') }} - {{ $weeklyReport->end_date->format('M d, Y') }}</p>
        </div>

        <div class="mb-10 space-y-6">
            @if($weeklyReport->executive_summary)
            <div>
                <h2 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Executive Summary</h2>
                <div class="prose prose-sm max-w-none text-gray-700 text-justify">
                    {!! $weeklyReport->executive_summary !!}
                </div>
            </div>
            @endif

        </div>

        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 border-b-2 border-indigo-500 pb-2 mb-6">Progress</h2>

            @php
            $groupedByModule = collect($groupedByModule)
            ->filter(function($subModules, $moduleName) {
            return !str_contains(strtolower($moduleName), 'nexusmed');
            })
            ->sortBy(function($subModules, $moduleName) {
            return str_contains(strtolower($moduleName), 'general') ? 0 : 1;
            });
            @endphp

            @foreach($groupedByModule as $moduleName => $subModules)
            <div class="mb-8">
                <h3 class="text-xl font-bold text-indigo-700 mb-4 flex items-center gap-3">
                    {{ $moduleName }}
                    @php
                    $firstReport = collect($subModules)->first()->first();
                    $modulePhase = $firstReport && $firstReport->subModule && $firstReport->subModule->module ? $firstReport->subModule->module->phase : null;
                    @endphp
                    @if($modulePhase)
                    @php
                    $isTesting = str_contains(strtolower($modulePhase), 'testing');
                    $badgeClasses = $isTesting ? 'bg-green-100 text-green-800 border-green-200' : 'bg-blue-100 text-blue-800 border-blue-200';
                    @endphp
                    <span class="{{ $badgeClasses }} text-sm font-medium px-2.5 py-0.5 rounded border">{{ ucfirst($modulePhase) }}</span>
                    @endif
                </h3>

                @foreach($subModules as $subModuleName => $reports)
                @php
                $subModuleId = $reports->first()->sub_module_id;
                $progressRecord = $weeklyReport->weeklyReportProgresses->where('sub_module_id', $subModuleId)->first();
                @endphp

                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="text-lg font-semibold text-gray-800">{{ $subModuleName }}</h4>
                        @if($progressRecord && $progressRecord->progress_percentage !== null)
                        @php
                        $isComplete = $progressRecord->progress_percentage == 100;
                        $progressClass = $isComplete ? 'bg-green-100 text-green-800' : 'bg-indigo-100 text-indigo-800';
                        @endphp
                        <span class="{{ $progressClass }} text-sm font-medium px-3 py-1 rounded-full">
                            Progress: {{ $progressRecord->progress_percentage }}%
                        </span>
                        @endif
                    </div>

                    <div class="space-y-3 mb-2">
                        @foreach($reports as $report)
                            @php
                                $rawDesc = $report->getRawOriginal('description') ?? $report->description;
                                $tasks = \App\Models\DailyReport::parseTasks($rawDesc);
                            @endphp
                            @foreach($tasks as $task)
                                @if(preg_match('/^[A-Za-z0-9\s\-_()\/&]+(:|(\s*\|\s*[A-Za-z0-9\s\-_()\/&]+)+)$/u', $task))
                                    <div class="font-semibold text-xs text-indigo-700 mt-3 mb-1 uppercase tracking-wide">{{ $task }}</div>
                                @else
                                    <div class="flex items-start text-gray-700">
                                        <div class="w-2 h-2 rounded-full bg-indigo-500 mt-2 mr-3 flex-shrink-0"></div>
                                        <div class="prose prose-sm max-w-none [&>*:last-child]:mb-0 w-full">{{ $task }}</div>
                                    </div>
                                @endif
                            @endforeach
                        @endforeach
                    </div>

                    @php
                    $allImages = $reports->flatMap->reportImages;
                    @endphp

                    @if($allImages->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4">
                        @foreach($allImages as $image)
                        <div class="flex flex-col items-center justify-center">
                            <img src="{{ Storage::url($image->image_path) }}" alt="{{ $image->caption }}" class="rounded-lg object-contain max-h-40 max-w-full shadow-sm border border-gray-200">
                            @if($image->caption)
                            <p class="text-sm text-gray-500 mt-2 text-center">{{ $image->caption }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endforeach
        </div>

        @if($weeklyReport->plan_of_action)
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 border-b-2 border-indigo-500 pb-2 mb-6">Plan of Actions</h2>
            <div class="prose prose-sm max-w-none text-gray-700">
                {!! $weeklyReport->plan_of_action !!}
            </div>
        </div>
        @endif

        <div>
            <h2 class="text-2xl font-bold text-gray-800 border-b-2 border-indigo-500 pb-2 mb-6">Individual Tasks</h2>

            <div class="space-y-6">
                @foreach($groupedByUser as $userName => $reports)
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <!-- <svg class="h-4 w-4 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg> -->
                        {{ $userName }}
                    </h3>
                    @php
                    $groupedByModule = collect($reports)->groupBy(function($report) {
                    $moduleName = strtolower(optional(optional($report->subModule)->module)->name ?? '');
                    return str_contains($moduleName, 'nexusmed') ? 'Nexusmed' : 'Medicare';
                    });
                    @endphp

                    <div class="space-y-5 mt-4">
                        @foreach($groupedByModule as $groupTitle => $groupReports)
                        <div>
                            <h4 class="text-sm font-bold text-gray-700 mb-3 bg-gray-100 inline-block px-3 py-1 rounded-md">{{ $groupTitle }}</h4>
                            <div class="space-y-3 pl-2">
                                @foreach($groupReports as $report)
                                    @php
                                        $rawDesc = $report->getRawOriginal('description') ?? $report->description;
                                        $tasks = \App\Models\DailyReport::parseTasks($rawDesc);
                                    @endphp
                                    @foreach($tasks as $task)
                                        @if(preg_match('/^[A-Za-z0-9\s\-_()\/&]+(:|(\s*\|\s*[A-Za-z0-9\s\-_()\/&]+)+)$/u', $task))
                                            <div class="font-semibold text-xs text-indigo-700 mt-3 mb-1 uppercase tracking-wide">{{ $task }}</div>
                                        @else
                                            <div class="flex items-start text-gray-700">
                                                <div class="w-2 h-2 rounded-full bg-indigo-500 mt-2 mr-3 flex-shrink-0"></div>
                                                <div class="prose prose-sm max-w-none [&>*:last-child]:mb-0 w-full">{{ $task }}</div>
                                            </div>
                                        @endif
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</body>

</html>