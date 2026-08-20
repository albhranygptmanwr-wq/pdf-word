document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const browseBtn = document.getElementById('browse-btn');
    
    if (!dropZone) return; // Only execute on converter page

    const fileInfo = document.getElementById('file-info');
    const filenameEl = document.getElementById('filename');
    const filesizeEl = document.getElementById('filesize');
    const removeFileBtn = document.getElementById('remove-file');
    const convertBtn = document.getElementById('convert-btn');
    
    const progressArea = document.getElementById('progress-area');
    const progressBar = document.getElementById('progress-bar');
    const progressPercentage = document.getElementById('progress-percentage');
    
    const successArea = document.getElementById('success-area');
    const resultFilename = document.getElementById('result-filename');
    const downloadBtn = document.getElementById('download-btn');
    const convertAnotherBtn = document.getElementById('convert-another-btn');
    
    const errorAlert = document.getElementById('error-alert');

    let currentFile = null;

    // Events
    browseBtn.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) handleFile(e.target.files[0]);
    });

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
    });

    removeFileBtn.addEventListener('click', resetUI);
    convertAnotherBtn.addEventListener('click', resetUI);

    convertBtn.addEventListener('click', uploadAndConvert);

    function handleFile(file) {
        errorAlert.classList.add('hidden');
        if (file.type !== 'application/pdf') {
            showError('الرجاء اختيار ملف PDF صالح.');
            return;
        }
        if (file.size > 50 * 1024 * 1024) {
            showError('حجم الملف أكبر من 50MB.');
            return;
        }

        currentFile = file;
        filenameEl.textContent = file.name;
        filesizeEl.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        
        dropZone.classList.add('hidden');
        fileInfo.classList.remove('hidden');
    }

    function resetUI() {
        currentFile = null;
        fileInput.value = '';
        dropZone.classList.remove('hidden');
        fileInfo.classList.add('hidden');
        progressArea.classList.add('hidden');
        successArea.classList.add('hidden');
        errorAlert.classList.add('hidden');
        progressBar.style.width = '0%';
        progressPercentage.textContent = '0%';
    }

    function showError(msg) {
        errorAlert.textContent = msg;
        errorAlert.classList.remove('hidden');
    }

    function uploadAndConvert() {
        if (!currentFile) return;

        fileInfo.classList.add('hidden');
        progressArea.classList.remove('hidden');
        errorAlert.classList.add('hidden');

        const formData = new FormData();
        formData.append('pdf', currentFile);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/convert.php', true);

        // Upload progress
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                // Upload phase (0-50%)
                let percentComplete = Math.round((e.loaded / e.total) * 50);
                updateProgress(percentComplete);
            }
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        // Simulate conversion phase progress (50-100%)
                        simulateConversionProgress(() => {
                            progressArea.classList.add('hidden');
                            successArea.classList.remove('hidden');
                            resultFilename.textContent = res.name;
                            downloadBtn.href = res.download;
                        });
                    } else {
                        throw new Error(res.message || 'فشل التحويل.');
                    }
                } catch (e) {
                    failProcess(e.message);
                }
            } else {
                failProcess('حدث خطأ في الخادم.');
            }
        };

        xhr.onerror = () => failProcess('خطأ في الاتصال بالشبكة.');
        xhr.send(formData);
    }

    function updateProgress(percent) {
        progressBar.style.width = percent + '%';
        progressPercentage.textContent = percent + '%';
    }

    function simulateConversionProgress(callback) {
        let current = 50;
        const interval = setInterval(() => {
            current += Math.floor(Math.random() * 10) + 5;
            if (current >= 100) {
                current = 100;
                clearInterval(interval);
                updateProgress(100);
                setTimeout(callback, 500);
            } else {
                updateProgress(current);
            }
        }, 300);
    }

    function failProcess(msg) {
        progressArea.classList.add('hidden');
        fileInfo.classList.remove('hidden');
        showError(msg);
    }
});