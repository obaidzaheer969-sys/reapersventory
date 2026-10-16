<div>
    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[1fr_280px]">
        <div class="space-y-4">
            <div class="rounded-xl border border-brand-200 bg-brand-50/50 p-3">
                <label class="block text-sm font-semibold text-navy-900">Scan barcode</label>
                <input
                    type="text"
                    wire:model="barcodeScan"
                    wire:keydown.enter.prevent="scanBarcode"
                    autofocus
                    autocomplete="off"
                    placeholder="Scan barcode / SKU then Enter"
                    class="mt-1 w-full rounded-lg px-3 py-3 text-base ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                <div class="mt-2 flex flex-wrap gap-2">
                    <button type="button" id="sale-camera-start" class="rounded-lg border border-brand-300 px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-100">Scan with phone camera</button>
                    <button type="button" id="sale-camera-stop" hidden class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Stop camera</button>
                </div>
                <div id="sale-camera-panel" hidden class="mt-3">
                    <video id="sale-camera-video" autoplay muted playsinline class="w-full max-w-md rounded-lg bg-black"></video>
                </div>
                <p id="sale-camera-status" class="mt-2 text-xs text-slate-600" role="status" aria-live="polite"></p>
                <p class="mt-1 text-xs text-slate-500">USB scanner: connect it, focus this field, then scan; it sends Enter automatically. Phone camera scanning requires browser camera support.</p>
                @error('barcodeScan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium">Customer</label>
                    <select wire:model.live="customerSelection" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="walk_in">Walk-in customer</option>
                        <option value="online">Online order</option>
                        @foreach ($customers as $customer)
                            <option value="customer:{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Walk-in, online order, ya customer name select karo.</p>
                    @error('customerId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('customerSelection') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Warehouse</label>
                    <select wire:model="warehouseId" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouseId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Sale date</label>
                    <input type="date" wire:model="saleDate" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium">Tax %</label>
                        <select wire:model.live="taxPercent" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            @foreach ($taxOptions as $option)
                                <option value="{{ $option }}">{{ $option }}%</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Discount %</label>
                        <select wire:model.live="discountPercent" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            @foreach ($discountOptions as $option)
                                <option value="{{ $option }}">{{ $option }}%</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium">Payment now</label>
                    <div class="mt-1 flex gap-2">
                        <input type="number" step="0.01" wire:model.live="paymentAmount" class="w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <button type="button" wire:click="payFullAmount" class="whitespace-nowrap rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-navy-900 hover:bg-slate-50">Pay full</button>
                    </div>
                    @error('paymentAmount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Payment method</label>
                    <select wire:model="paymentMethod" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank transfer</option>
                        <option value="easypaisa">Easypaisa</option>
                        <option value="jazzcash">JazzCash</option>
                        <option value="card">Debit or Credit card</option>
                    </select>
                    @error('paymentMethod') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-navy-900">Items</h3>
                    <button type="button" wire:click="addItem" class="text-sm font-semibold text-brand-600">Add line</button>
                </div>
                @foreach ($items as $index => $item)
                    <div class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-4" wire:key="sale-item-{{ $index }}">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium">Product</label>
                            <select wire:model.live="items.{{ $index }}.product_id" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                                <option value="">Select</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                            @error("items.$index.product_id") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium">Qty</label>
                            <input type="number" step="0.001" wire:model.live="items.{{ $index }}.quantity" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium">Unit price</label>
                            <div class="mt-1 flex gap-2">
                                <input type="number" step="0.01" wire:model.live="items.{{ $index }}.unit_price" class="w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                                @if (count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})" class="text-sm text-red-600">✕</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                @error('items') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <aside class="lg:sticky lg:top-4 h-fit rounded-2xl border border-navy-900/10 bg-navy-950 p-4 text-white shadow-lg">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-400">Bill preview</p>
            <p class="mt-1 font-mono text-[11px] text-slate-400">80mm thermal style</p>
            <div class="mt-4 space-y-2 font-mono text-sm">
                <div class="flex justify-between gap-3"><span class="text-slate-300">Subtotal</span><span>{{ number_format((float) $this->subtotal, 2) }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-300">Tax {{ $taxPercent }}%</span><span>{{ number_format((float) $this->taxAmount, 2) }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-300">Discount {{ $discountPercent }}%</span><span>{{ number_format((float) $this->discountAmount, 2) }}</span></div>
                <div class="flex justify-between gap-3 border-t border-white/10 pt-3 text-lg font-bold">
                    <span>TOTAL</span>
                    <span class="text-brand-400">{{ number_format((float) $this->grandTotal, 2) }}</span>
                </div>
                <div class="flex justify-between gap-3 text-xs text-slate-400">
                    <span>Paying now</span>
                    <span>{{ number_format((float) $paymentAmount, 2) }}</span>
                </div>
            </div>
            <button type="submit" class="mt-5 w-full rounded-lg bg-brand-500 px-4 py-3 font-semibold text-white hover:bg-brand-600">
                Complete &amp; print bill
            </button>
            <p class="mt-2 text-center text-[11px] text-slate-400">Invoice auto-save + small bill print dialog</p>
        </aside>
    </form>
</div>
@pushOnce('scripts')
<script>
  (function () {
        var cameraStart = document.getElementById('sale-camera-start');
        var cameraStop = document.getElementById('sale-camera-stop');
        var cameraPanel = document.getElementById('sale-camera-panel');
        var cameraVideo = document.getElementById('sale-camera-video');
        var cameraStatus = document.getElementById('sale-camera-status');
        var cameraControls = null;
        var cameraStream = null;
        var isScanning = false;

        function stopCamera() {
            isScanning = false;
            if (cameraControls) {
                cameraControls.stop();
                cameraControls = null;
            }
            if (cameraStream) {
                cameraStream.getTracks().forEach(function (track) {
                    track.stop();
                });
                cameraStream = null;
            }
            if (cameraVideo) {
                cameraVideo.srcObject = null;
            }
            if (cameraPanel) {
                cameraPanel.hidden = true;
            }
            if (cameraStart) {
                cameraStart.hidden = false;
            }
            if (cameraStop) {
                cameraStop.hidden = true;
            }
        }

        function sendBarcodeToSale(code) {
            if (!code || typeof Livewire === 'undefined') {
                return;
            }
            var root = document.querySelector('[wire\\:id]');
            if (!root) {
                return;
            }
            var component = Livewire.find(root.getAttribute('wire:id'));
            if (!component) {
                return;
            }
            component.set('barcodeScan', code).then(function () {
                return component.callMethod('scanBarcode');
            }).catch(function () {
                var input = document.querySelector('input[wire\\:model="barcodeScan"]');
                if (!input) {
                    return;
                }
                input.value = code;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new KeyboardEvent('keydown', {
                    key: 'Enter', code: 'Enter', bubbles: true, cancelable: true
                }));
            });
        }

        function startCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                cameraStatus.textContent = 'Camera is unavailable. Open this page over HTTPS, or use a USB scanner.';
                return;
            }

            cameraStatus.textContent = 'Requesting camera access...';
            cameraPanel.hidden = false;
            cameraStart.hidden = true;
            cameraStop.hidden = false;
            isScanning = true;
            var streamRequest;
            try {
                streamRequest = navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false
                });
            } catch (error) {
                stopCamera();
                cameraStatus.textContent = 'Could not request the camera. Allow camera access and try again.';
                return;
            }

            streamRequest.then(function (stream) {
                cameraStream = stream;
                if (!isScanning) {
                    stopCamera();
                    return null;
                }
                if (!window.ReapersVentoryBarcodeScanner) {
                    throw new Error('Scanner module is not loaded.');
                }
                cameraVideo.srcObject = stream;
                return cameraVideo.play().then(function () {
                    return window.ReapersVentoryBarcodeScanner.start(cameraVideo, stream, function (code) {
                        if (!isScanning) {
                            return;
                        }
                        stopCamera();
                        cameraStatus.textContent = 'Barcode detected. Adding product...';
                        sendBarcodeToSale(code);
                    });
                });
            }).then(function (controls) {
                if (!controls) {
                    return;
                }
                cameraControls = controls;
                if (!isScanning) {
                    stopCamera();
                    return;
                }
                cameraStatus.textContent = 'Point the camera at a product barcode.';
            }).catch(function () {
                var wasScanning = isScanning;
                stopCamera();
                if (wasScanning) {
                    cameraStatus.textContent = 'Could not start scanning. Allow camera access and use HTTPS.';
                }
            });
        }

        if (cameraStart) {
            cameraStart.addEventListener('click', startCamera);
        }
        if (cameraStop) {
            cameraStop.addEventListener('click', function () {
                stopCamera();
                cameraStatus.textContent = 'Camera stopped.';
            });
        }
        document.addEventListener('livewire:navigating', stopCamera);

    // Receive barcode scanned from the Expo mobile app camera and feed it into
    // the same Livewire sale-form scanner that the USB keyboard scanner uses.
    window.addEventListener('message', function (event) {
      var data = event.data;
            if (typeof data === 'string') {
                try {
                    data = JSON.parse(data);
                } catch (error) {
                    return;
                }
            }
      if (!data || data.type !== 'rv-mobile-barcode') {
        return;
      }
            sendBarcodeToSale(data.code);
    });
  })();
</script>
@endPushOnce
