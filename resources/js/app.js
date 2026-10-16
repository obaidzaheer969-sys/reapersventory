window.ReapersVentoryBarcodeScanner = {
	async start(video, stream, onDetected) {
		const { BrowserMultiFormatReader } = await import('@zxing/browser');
		const reader = new BrowserMultiFormatReader();
		let controls;
		let detected = false;

		controls = await reader.decodeFromStream(
			stream,
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
