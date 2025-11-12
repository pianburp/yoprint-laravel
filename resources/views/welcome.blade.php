<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'YoPrint Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-100 text-gray-800 flex items-center justify-center min-h-screen">
        <div class="container mx-auto p-4">
            <div class="bg-white shadow-md rounded p-6">
                <h1 class="text-2xl font-bold mb-4">Upload CSV File</h1>
                <form id="upload-form" class="mb-4">
                    <div class="flex items-center justify-center w-full mb-4">
                        <label for="file-upload" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-3 text-gray-400" viewBox="0 0 24 24" fill="none">
                                    <path d="M12.5535 2.49392C12.4114 2.33852 12.2106 2.25 12 2.25C11.7894 2.25 11.5886 2.33852 11.4465 2.49392L7.44648 6.86892C7.16698 7.17462 7.18822 7.64902 7.49392 7.92852C7.79963 8.20802 8.27402 8.18678 8.55352 7.88108L11.25 4.9318V16C11.25 16.4142 11.5858 16.75 12 16.75C12.4142 16.75 12.75 16.4142 12.75 16V4.9318L15.4465 7.88108C15.726 8.18678 16.2004 8.20802 16.5061 7.92852C16.8118 7.64902 16.833 7.17462 16.5535 6.86892L12.5535 2.49392Z" fill="#1C274C"/>
                                    <path d="M3.75 15C3.75 14.5858 3.41422 14.25 3 14.25C2.58579 14.25 2.25 14.5858 2.25 15V15.0549C2.24998 16.4225 2.24996 17.5248 2.36652 18.3918C2.48754 19.2919 2.74643 20.0497 3.34835 20.6516C3.95027 21.2536 4.70814 21.5125 5.60825 21.6335C6.47522 21.75 7.57754 21.75 8.94513 21.75H15.0549C16.4225 21.75 17.5248 21.75 18.3918 21.6335C19.2919 21.5125 20.0497 21.2536 20.6517 20.6516C21.2536 20.0497 21.5125 19.2919 21.6335 18.3918C21.75 17.5248 21.75 16.4225 21.75 15.0549V15C21.75 14.5858 21.4142 14.25 21 14.25C20.5858 14.25 20.25 14.5858 20.25 15C20.25 16.4354 20.2484 17.4365 20.1469 18.1919C20.0482 18.9257 19.8678 19.3142 19.591 19.591C19.3142 19.8678 18.9257 20.0482 18.1919 20.1469C17.4365 20.2484 16.4354 20.25 15 20.25H9C7.56459 20.25 6.56347 20.2484 5.80812 20.1469C5.07435 20.0482 4.68577 19.8678 4.40901 19.591C4.13225 19.3142 3.9518 18.9257 3.85315 18.1919C3.75159 17.4365 3.75 16.4354 3.75 15Z" fill="#1C274C"/>
                                </svg>
                                <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                <p class="text-xs text-gray-500">CSV files only</p>
                            </div>
                            <input id="file-upload" type="file" class="hidden" accept=".csv" />
                        </label>
                    </div>
                    <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">Upload</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-500">Time</th>
                                <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-500">File Name</th>
                                <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody id="file-table-body">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">There's no file to show.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('upload-form').addEventListener('submit', async function(event) {
                event.preventDefault();
                const fileInput = document.getElementById('file-upload');
                const file = fileInput.files[0];

                if (!file) {
                    alert('Please select a file to upload.');
                    return;
                }

                if (!file.name.endsWith('.csv')) {
                    alert('Only CSV files are allowed.');
                    return;
                }

                // Create FormData and append the file
                const formData = new FormData();
                formData.append('file', file);

                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    // Show uploading status immediately
                    const tableBody = document.getElementById('file-table-body');
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = `
                        <td class="px-6 py-4 border-b text-sm text-gray-700">${new Date().toLocaleString()}</td>
                        <td class="px-6 py-4 border-b text-sm text-gray-700">${file.name}</td>
                        <td class="px-6 py-4 border-b text-sm text-blue-500">Uploading...</td>
                    `;
                    
                    if (tableBody.querySelector('td[colspan="3"]')) {
                        tableBody.innerHTML = '';
                    }
                    tableBody.insertBefore(newRow, tableBody.firstChild);

                    // Upload the file
                    const response = await fetch('/upload', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        // Update the row to show pending status
                        newRow.innerHTML = `
                            <td class="px-6 py-4 border-b text-sm text-gray-700">${new Date().toLocaleString()}</td>
                            <td class="px-6 py-4 border-b text-sm text-gray-700">${file.name}</td>
                            <td class="px-6 py-4 border-b text-sm text-yellow-500">Pending</td>
                        `;
                        
                        // Clear file input
                        fileInput.value = '';
                        
                        // Fetch updated list
                        setTimeout(() => fetchRecentUploads(), 1000);
                    } else {
                        alert(result.message || 'Failed to upload file.');
                        newRow.remove();
                        if (tableBody.children.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">There\'s no file to show.</td></tr>';
                        }
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    alert('An error occurred while uploading the file.');
                    await fetchRecentUploads();
                }
            });

            document.addEventListener('dragover', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const dropArea = document.querySelector('label[for="file-upload"]');
                dropArea.classList.add('bg-blue-100', 'border-blue-400');
            });

            document.addEventListener('dragleave', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const dropArea = document.querySelector('label[for="file-upload"]');
                dropArea.classList.remove('bg-blue-100', 'border-blue-400');
            });

            document.addEventListener('drop', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const dropArea = document.querySelector('label[for="file-upload"]');
                dropArea.classList.remove('bg-blue-100', 'border-blue-400');

                const fileInput = document.getElementById('file-upload');
                const files = event.dataTransfer.files;

                if (files.length > 0) {
                    fileInput.files = files;
                    const uploadForm = document.getElementById('upload-form');
                    uploadForm.dispatchEvent(new Event('submit'));
                }
            });

            async function fetchRecentUploads() {
                try {
                    const response = await fetch('/recent-uploads');
                    if (!response.ok) {
                        throw new Error('Failed to fetch recent uploads');
                    }

                    const uploads = await response.json();
                    const tableBody = document.getElementById('file-table-body');
                    tableBody.innerHTML = '';

                    if (uploads.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">There\'s no file to show.</td></tr>';
                        return;
                    }

                    uploads.forEach(upload => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="px-6 py-4 border-b text-sm text-gray-700">${new Date(upload.time).toLocaleString()}</td>
                            <td class="px-6 py-4 border-b text-sm text-gray-700">${upload.fileName}</td>
                            <td class="px-6 py-4 border-b text-sm ${upload.status === 'Completed' ? 'text-green-500' : 'text-yellow-500'}">${upload.status}</td>
                        `;
                        tableBody.appendChild(row);
                    });
                } catch (error) {
                    console.error(error);
                }
            }

            function startRealTimeUpdates() {
                setInterval(async () => {
                    await fetchRecentUploads();
                }, 5000); // Poll every 5 seconds
            }

            // Fetch recent uploads on page load
            document.addEventListener('DOMContentLoaded', () => {
                fetchRecentUploads();
                startRealTimeUpdates();
            });
        </script>
    </body>
</html>
