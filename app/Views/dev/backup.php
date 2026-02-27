<?php $title = "Backup DEV"; ?>

<div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded-xl shadow">

    <h2 class="text-xl font-bold text-red-700 mb-4">
        ⚠ Backup environnement DEV
    </h2>

    <button id="startBackup"
        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold">
        Lancer le backup
    </button>

    <div class="mt-6">
        <div class="w-full bg-gray-200 rounded-full h-4">
            <div id="progressBar"
                 class="bg-red-600 h-4 rounded-full transition-all duration-300"
                 style="width:0%">
            </div>
        </div>
        <div id="progressText" class="text-sm mt-2 text-gray-600">
            0%
        </div>
    </div>

</div>

<script>
document.getElementById('startBackup').addEventListener('click', function () {

    fetch('/admin/dev-backup/start', {method: 'POST'});

    const interval = setInterval(() => {
        fetch('/admin/dev-backup/progress')
            .then(res => res.json())
            .then(data => {

                let percent = data.progress;
                document.getElementById('progressBar').style.width = percent + '%';
                document.getElementById('progressText').innerText = percent + '%';

                if (percent >= 100) {
                    clearInterval(interval);
                    window.location.href = '/admin/dev-backup/download';
                }
            });

    }, 800);
});
</script>