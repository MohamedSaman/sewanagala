<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Receipt - {{ $sale->invoice_number }}</title>
    <style>
        /* Professional Dot-Matrix Setup */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            background: #fdfdfd; 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 11pt; 
            color: #000;
        }

        /* Screen controls for calibration */
        .sidebar {
            width: 320px;
            background: #2b3343; /* Dark sidebar */
            color: #d1d5db;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 15px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .main-content {
            margin-left: 320px;
            padding: 40px;
            background: #e5e7eb;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        /* Sidebar UI Styles */
        .sidebar h3 { color: #fff; margin-bottom: 15px; font-size: 16px; border-bottom: 1px solid #4b5563; padding-bottom: 10px; }
        
        .btn { display: block; width: 100%; padding: 8px 12px; margin-bottom: 10px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 13px; text-align: center; color: white; transition: 0.2s; }
        .btn-row { display: flex; gap: 10px; margin-bottom: 20px; }
        .btn-print { background: #10b981; flex: 1; }
        .btn-print:hover { background: #059669; }
        .btn-close { background: #ef4444; flex: 1; }
        .btn-close:hover { background: #dc2626; }
        .btn-save { background: #10b981; margin-top: 15px; }
        .btn-save:hover { background: #059669; }
        .btn-secondary { background: #6b7280; flex: 1; margin-bottom: 0;}
        .btn-secondary:hover { background: #4b5563; }
        
        .card { background: #1f2937; border-radius: 6px; padding: 15px; margin-bottom: 15px; border: 1px solid #374151; }
        .card h4 { color: #9ca3af; font-size: 13px; margin-bottom: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Offset controls */
        .offset-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; font-size: 13px; }
        .offset-row label { width: 60px; }
        .control-group { display: flex; align-items: center; background: #374151; border-radius: 4px; overflow: hidden; border: 1px solid #4b5563; }
        .control-group button { background: #4b5563; border: none; color: white; width: 30px; height: 30px; cursor: pointer; }
        .control-group button:hover { background: #6b7280; }
        .control-group input { width: 50px; height: 30px; border: none; text-align: center; background: #1f2937; color: white; font-size: 13px; font-weight: bold; }
        .control-group input:focus { outline: none; }
        
        /* D-Pad */
        .d-pad-container { display: flex; flex-direction: column; align-items: center; margin: 15px 0; }
        .d-pad-row { display: flex; justify-content: center; }
        .d-btn { width: 40px; height: 40px; background: #2563eb; color: white; border: none; border-radius: 6px; margin: 2px; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 0 #1d4ed8; }
        .d-btn:active { transform: translateY(2px); box-shadow: none; }
        .d-center { width: 40px; height: 40px; background: #374151; margin: 2px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #9ca3af; }

        .selected-box { background: #111827; padding: 10px; border-radius: 4px; font-size: 12px; margin-bottom: 15px; border-left: 3px solid #E65F1E; }
        .selected-box span { color: #E65F1E; font-family: monospace; display: block; margin-top: 4px; }
        
        .deltas { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 15px; color: #FB923C; font-family: monospace; background: #111827; padding: 8px; border-radius: 4px; }

        .instructions { font-size: 11px; color: #9ca3af; margin-top: 20px; line-height: 1.5; }
        .instructions strong { color: #d1d5db; }

        /* Step Select */
        .step-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; font-size: 13px; }
        .step-row select { background: #374151; color: white; border: 1px solid #4b5563; padding: 5px; border-radius: 4px; }

        /* The Paper Canvas - 9.5in x 5.5in */
        .page {
            width: 9.5in;
            height: 5.5in;
            background: #fff;
            position: relative;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border: 1px dashed #bbb;
            overflow: hidden;
            transition: transform 0.1s;
        }

        /* Print Logic */
        @media print {
            body { background: transparent !important; }
            .sidebar { display: none !important; }
            .main-content { margin: 0 !important; padding: 0 !important; background: transparent !important; display: block !important;}
            .page { 
                margin: 0 !important; 
                box-shadow: none !important; 
                border: none !important;
                page-break-after: always;
            }
            .selectable-item { border: none !important; cursor: auto !important; }
            .selected-element { outline: none !important; background: transparent !important; }
            @page {
                size: 9.5in 5.5in;
                margin: 0;
            }
        }

        /* Absolute Positioning System */
        .data-field { 
            position: absolute; 
            white-space: nowrap; 
        }
        
        /* Selectable Items */
        .selectable-item {
            cursor: crosshair;
            border: 1px solid transparent;
            transition: border-color 0.1s, background-color 0.1s;
        }
        .selectable-item:hover {
            border-color: rgba(59, 130, 246, 0.5);
            background-color: rgba(59, 130, 246, 0.05);
        }
        .selected-element {
            border-color: #E65F1E !important;
            background-color: rgba(230, 95, 30, 0.1) !important;
            outline: 1px solid #E65F1E;
            z-index: 10;
        }

        /**
         * CALIBRATION SECTION - Phoenix Bathware Pre-printed Template
         * REVERTED HEADERS TO 185mm | KEPT AMOUNTS AT 182mm
         */
        
        /* Header Box - Right Side (REVERTED to 185mm) */
        .date             { top: 14mm; left: 188mm; }
        .invoice-no       { top: 21mm; left: 188mm; }
        .sales-rep        { top: 27mm; left: 188mm; }
        .payment-method   { top: 33mm; left: 188mm; }
        
        /* Customer Box - Left Side */
        .customer-name    { top: 23mm; left: 35mm; width: 105mm; }
        .customer-address { top: 27.5mm; left: 35mm; width: 150mm; white-space: normal; line-height: 0.8; }
        .customer-phone   { top: 32mm; left: 35mm; }

        /* Item Row Layout - AMOUNTS KEPT AT 182mm */
        .item-row      { position: absolute; width: 9.5in; height: 6mm; left: 0; }
        .col-code      { position: absolute; left: 11mm; width: 25mm; }
        .col-desc      { position: absolute; left: 39mm; width: 40mm; overflow: hidden; }
        .col-qty       { position: absolute; left: 112mm; width: 15mm; text-align: right; }
        .col-price     { position: absolute; left: 132mm; width: 30mm; text-align: right; }
        .col-discount  { position: absolute; left: 169mm; width: 15mm; text-align: right; }
        .col-total     { position: absolute; left: 182mm; width: 35mm; text-align: right; }

        /* Summary Area - KEPT AT 182mm */
        .subtotal      { top: 100mm; left: 182mm; text-align: right; width: 35mm; }
        .discount      { top: 104.5mm; left: 182mm; text-align: right; width: 35mm; }
        .grand-total   { top: 109mm; left: 182mm; text-align: right; width: 35mm; font-weight: bold; }
        
        .paid-amount   { top: 113.5mm; left: 182mm; text-align: right; width: 35mm; }
        .balance-amount{ top: 118mm; left: 182mm; text-align: right; width: 35mm; font-weight: bold; }
        .due-date      { top: 122.5mm; left: 182mm; text-align: right; width: 35mm; font-size: 9pt; }

    </style>
</head>
<body onload="@if(!request()->has('view_only')) window.print(); @endif">

    @php
        // Prepare data for absolute layout
        $billName = trim((string) (optional($sale->customer)->name ?? 'Walk-in Customer'));
        $billAddress = trim((string) (optional($sale->customer)->address ?? ''));
        $billPhone = trim((string) (optional($sale->customer)->phone ?? ''));
        
        // FORMAT INVOICE NUMBER: INV-20260403-0068 -> INV-2026-0068
        $invParts = explode('-', $sale->invoice_number);
        if (count($invParts) === 3) {
            $formattedInvoice = $invParts[0] . '-' . substr($invParts[1], 0, 4) . '-' . $invParts[2];
        } else {
            $formattedInvoice = $sale->invoice_number;
        }

        $paymentMethodLabels = [
            'cash' => 'Cash',
            'cheque' => 'CHQ',
            'bank_transfer' => 'BT',
            'credit_card' => 'CC',
        ];

        $paymentMethods = $sale->payments
            ->pluck('payment_method')
            ->filter()
            ->unique()
            ->values();

        if ($paymentMethods->count() > 1) {
            $methodText = $paymentMethods
                ->map(fn ($method) => $paymentMethodLabels[$method] ?? ucwords(str_replace('_', ' ', $method)))
                ->implode('+');
            $paymentLabel = $methodText;
        } elseif ($paymentMethods->count() === 1) {
            $singleMethod = $paymentMethods->first();
            $paymentLabel = $paymentMethodLabels[$singleMethod] ?? ucwords(str_replace('_', ' ', $singleMethod));
        } else {
            $paymentLabel = ($sale->due_amount ?? 0) > 0 ? 'Due' : 'Cash';
        }

        $displayPaid = min($sale->payments->sum('amount'), $sale->total_amount);
        $displayBalance = max(0, $sale->total_amount - $displayPaid);

        // TABLE CALIBRATION
        $rowStartY = 47; 
        $rowHeight = 6; 
    @endphp

    <!-- SIDEBAR CALIBRATION CONTROLS -->
    <div class="sidebar no-print">
        <h3>🖨️ Print & Calibration</h3>
        
        <div class="btn-row">
            <button class="btn btn-print" onclick="window.print()">PRINT NOW</button>
            <button class="btn btn-close" onclick="window.close()">CLOSE</button>
        </div>

        <div class="card">
            <h4>📐 Global Offset</h4>
            <div class="offset-row">
                <label>X offset:</label>
                <div class="control-group">
                    <button onclick="changeGlobal(-1, 0)">◀</button>
                    <input type="number" id="global-x" value="0" step="1" onchange="updateGlobalFromInput()">
                    <div style="background: #1f2937; padding: 0 5px; color: #9ca3af; font-size: 11px; display: flex; align-items: center;">mm</div>
                    <button onclick="changeGlobal(1, 0)">▶</button>
                </div>
            </div>
            <div class="offset-row">
                <label>Y offset:</label>
                <div class="control-group">
                    <button onclick="changeGlobal(0, -1)">▲</button>
                    <input type="number" id="global-y" value="0" step="1" onchange="updateGlobalFromInput()">
                    <div style="background: #1f2937; padding: 0 5px; color: #9ca3af; font-size: 11px; display: flex; align-items: center;">mm</div>
                    <button onclick="changeGlobal(0, 1)">▼</button>
                </div>
            </div>
            <button class="btn btn-secondary" style="width:100%; margin-top:10px;" onclick="resetGlobal()">Reset Global</button>
        </div>

        <div class="card">
            <h4>🎯 Element Offset</h4>
            
            <div class="selected-box">
                Selected:
                <span id="selected-label">[none]</span>
            </div>

            <div class="step-row">
                <label>Step:</label>
                <select id="nudge-step">
                    <option value="0.1">0.1 mm</option>
                    <option value="0.5" selected>0.5 mm</option>
                    <option value="1">1.0 mm</option>
                    <option value="5">5.0 mm</option>
                </select>
            </div>

            <div class="d-pad-container">
                <div class="d-pad-row">
                    <button class="d-btn" onclick="nudgeSelected(0, -1)">▲</button>
                </div>
                <div class="d-pad-row">
                    <button class="d-btn" onclick="nudgeSelected(-1, 0)">◀</button>
                    <div class="d-center">move</div>
                    <button class="d-btn" onclick="nudgeSelected(1, 0)">▶</button>
                </div>
                <div class="d-pad-row">
                    <button class="d-btn" onclick="nudgeSelected(0, 1)">▼</button>
                </div>
            </div>

            <div class="deltas">
                <div>ΔX: <span id="delta-x">0.0</span> mm</div>
                <div>ΔY: <span id="delta-y">0.0</span> mm</div>
            </div>

            <div class="btn-row">
                <button class="btn btn-secondary" onclick="resetSelected()">Reset This</button>
                <button class="btn btn-secondary" onclick="resetAllElements()">Reset All</button>
            </div>
        </div>

        <button class="btn btn-save" onclick="saveOffsets()">💾 Save All Offsets</button>

        <div class="instructions">
            <strong>How to use:</strong><br>
            1. Open with <code>?view_only=1</code> to preview without auto-print.<br>
            2. Click any element on the receipt to select it.<br>
            3. Nudge with the d-pad or keyboard arrows.<br>
            4. Hit <strong>Save All Offsets</strong> to persist.
        </div>
    </div>

    <!-- MAIN PRINT CANVAS -->
    <div class="main-content">
        <div class="page" id="print-page">
            <!-- HEADER -->
            <div class="data-field date selectable-item">{{ $sale->created_at->format('d/m/Y') }}</div>
            <div class="data-field invoice-no selectable-item">{{ $formattedInvoice }}</div>
            <div class="data-field sales-rep selectable-item">{{ substr($sale->user->name ?? '-', 0, 15) }}</div>
            <div class="data-field payment-method selectable-item">{{ $paymentLabel }}</div>
            
            <div class="data-field customer-name selectable-item">{{ $billName }}</div>
            <div class="data-field customer-address selectable-item">{{ substr($billAddress, 0, 100) }}</div>
            <div class="data-field customer-phone selectable-item">{{ $billPhone }}</div>

            <!-- PRODUCT ROWS -->
            @foreach($sale->items as $index => $item)
                @php $currentY = $rowStartY + ($index * $rowHeight); @endphp
                <div class="item-row" style="top: {{ $currentY }}mm;">
                    <span class="col-code selectable-item">{{ $item->product_code }}</span>
                    <span class="col-desc selectable-item">{{ substr($item->product_name, 0, 30) }}</span>
                    <span class="col-qty selectable-item">{{ number_format($item->quantity, 0) }}</span>
                    <span class="col-price selectable-item">{{ number_format($item->unit_price) }}</span>
                    <span class="col-discount selectable-item">@if($item->discount_per_unit > 0){{ number_format($item->discount_per_unit) }}@endif</span>
                    <span class="col-total selectable-item">{{ number_format($item->total) }}</span>
                </div>
            @endforeach

            <!-- TOTALS AREA -->
            <div class="data-field subtotal selectable-item">{{ number_format($sale->subtotal) }}</div>
            <div class="data-field discount selectable-item">{{ number_format($sale->discount_amount) }}</div>
            <div class="data-field grand-total selectable-item">{{ number_format($sale->total_amount) }}</div>
            <div class="data-field paid-amount selectable-item">{{ number_format($displayPaid) }}</div>
            <div class="data-field balance-amount selectable-item">{{ number_format($displayBalance) }}</div>
            <div class="data-field due-date selectable-item">@if($displayBalance > 0 && $sale->due_date){{ \Carbon\Carbon::parse($sale->due_date)->format('d/m/Y') }}@else None @endif</div>
        </div>
    </div>

    <!-- CALIBRATION SCRIPT -->
    <script>
        const STORAGE_KEY = 'phoenix_print_offsets';
        let offsets = JSON.parse(localStorage.getItem(STORAGE_KEY)) || { global: {x: 0, y: 0}, elements: {} };
        let selectedKey = null;

        document.addEventListener('DOMContentLoaded', () => {
            applyAllOffsets();
            updateGlobalUI();

            // Setup click listeners for selectable items
            document.querySelectorAll('.selectable-item').forEach(el => {
                el.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectElement(el);
                });
            });

            // Deselect on background click
            document.querySelector('.main-content').addEventListener('click', () => {
                deselectAll();
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (!selectedKey) return;
                
                // Prevent default scrolling for arrow keys
                if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                    e.preventDefault();
                }

                switch(e.key) {
                    case 'ArrowUp': nudgeSelected(0, -1); break;
                    case 'ArrowDown': nudgeSelected(0, 1); break;
                    case 'ArrowLeft': nudgeSelected(-1, 0); break;
                    case 'ArrowRight': nudgeSelected(1, 0); break;
                }
            });
        });

        function getElementKey(el) {
            // Find the class that acts as the key (not common structural classes)
            const ignoreClasses = ['data-field', 'selectable-item', 'selected-element'];
            for (let cls of el.classList) {
                if (!ignoreClasses.includes(cls)) return cls;
            }
            return null;
        }

        function selectElement(el) {
            deselectAll();
            el.classList.add('selected-element');
            
            const key = getElementKey(el);
            selectedKey = key;

            // Highlight all elements with this key
            document.querySelectorAll('.' + key).forEach(sibling => {
                if(sibling !== el && sibling.classList.contains('selectable-item')) {
                    sibling.classList.add('selected-element');
                }
            });

            // Read text preview
            let textPreview = el.innerText.substring(0, 15);
            if (el.innerText.length > 15) textPreview += '...';
            document.getElementById('selected-label').innerText = `[${key}] ${textPreview}`;

            // Update delta UI
            if (!offsets.elements[key]) {
                offsets.elements[key] = {x: 0, y: 0};
            }
            updateDeltaUI();
        }

        function deselectAll() {
            document.querySelectorAll('.selected-element').forEach(el => {
                el.classList.remove('selected-element');
            });
            selectedKey = null;
            document.getElementById('selected-label').innerText = '[none]';
            document.getElementById('delta-x').innerText = '0.0';
            document.getElementById('delta-y').innerText = '0.0';
        }

        // GLOBAL CONTROLS
        function changeGlobal(dx, dy) {
            offsets.global.x += dx;
            offsets.global.y += dy;
            updateGlobalUI();
            applyAllOffsets();
        }

        function updateGlobalFromInput() {
            offsets.global.x = parseFloat(document.getElementById('global-x').value) || 0;
            offsets.global.y = parseFloat(document.getElementById('global-y').value) || 0;
            applyAllOffsets();
        }

        function resetGlobal() {
            offsets.global = {x: 0, y: 0};
            updateGlobalUI();
            applyAllOffsets();
        }

        function updateGlobalUI() {
            document.getElementById('global-x').value = offsets.global.x;
            document.getElementById('global-y').value = offsets.global.y;
        }

        // ELEMENT CONTROLS
        function nudgeSelected(dirX, dirY) {
            if (!selectedKey) {
                alert("Please select an element on the receipt first.");
                return;
            }

            const step = parseFloat(document.getElementById('nudge-step').value) || 0.5;
            
            if (!offsets.elements[selectedKey]) {
                offsets.elements[selectedKey] = {x: 0, y: 0};
            }

            offsets.elements[selectedKey].x += (dirX * step);
            offsets.elements[selectedKey].y += (dirY * step);

            // Fix floating point errors
            offsets.elements[selectedKey].x = Math.round(offsets.elements[selectedKey].x * 10) / 10;
            offsets.elements[selectedKey].y = Math.round(offsets.elements[selectedKey].y * 10) / 10;

            updateDeltaUI();
            applyAllOffsets();
        }

        function resetSelected() {
            if (!selectedKey) return;
            offsets.elements[selectedKey] = {x: 0, y: 0};
            updateDeltaUI();
            applyAllOffsets();
        }

        function resetAllElements() {
            if(confirm("Are you sure you want to reset all element offsets?")) {
                offsets.elements = {};
                if(selectedKey) updateDeltaUI();
                applyAllOffsets();
            }
        }

        function updateDeltaUI() {
            if (!selectedKey) return;
            const data = offsets.elements[selectedKey] || {x: 0, y: 0};
            document.getElementById('delta-x').innerText = data.x.toFixed(1);
            document.getElementById('delta-y').innerText = data.y.toFixed(1);
        }

        // APPLY OFFSETS TO DOM
        function applyAllOffsets() {
            // 1. Apply Global to page
            const page = document.getElementById('print-page');
            page.style.transform = `translate(${offsets.global.x}mm, ${offsets.global.y}mm)`;

            // 2. Apply Element offsets
            // First reset all transform
            document.querySelectorAll('.selectable-item').forEach(el => {
                el.style.transform = '';
            });

            // Then apply saved
            for (const [key, data] of Object.entries(offsets.elements)) {
                if (data.x === 0 && data.y === 0) continue;
                
                document.querySelectorAll('.' + key).forEach(el => {
                    if (el.classList.contains('selectable-item')) {
                        el.style.transform = `translate(${data.x}mm, ${data.y}mm)`;
                    }
                });
            }
        }

        // PERSIST
        function saveOffsets() {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(offsets));
            const btn = document.querySelector('.btn-save');
            const origText = btn.innerText;
            btn.innerText = '✅ Saved Successfully!';
            btn.style.background = '#059669';
            setTimeout(() => {
                btn.innerText = origText;
                btn.style.background = '#10b981';
            }, 2000);
        }

    </script>
</body>
</html>