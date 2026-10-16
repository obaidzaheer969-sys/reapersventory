window.ReapersVentoryBarcodeScanner = {
	async start(video, onDetected) {
		const { BrowserMultiFormatReader } = await import('@zxing/browser');
		const reader = new BrowserMultiFormatReader();
		let controls;
		let detected = false;

		controls = await reader.decodeFromConstraints(
			{
				video: { facingMode: { ideal: 'environment' } },
				audio: false,
			},
			video,
			(result) => {
				if (!result || detected) {
					return;
				}

				detected = true;
				controls?.stop();
				onDetected(result.getText());
			},
		);

		if (detected) {
			controls.stop();
		}

		return controls;
	},
};
