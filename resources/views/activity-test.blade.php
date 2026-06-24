<!DOCTYPE html>
<html>
<head>
    <title>Activity Test</title>
</head>
<body>

    <h2>Update Activity</h2>

    <form id="activityForm">
        <input type="text" name="title" placeholder="Title" required><br><br>

        <input type="text" name="category" value="General" required><br><br>

        <select name="priority">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
        </select><br><br>

        <input type="file" name="thumbnail"><br><br>

        <input type="file" name="attachments[]" multiple><br><br>

        <button type="submit">Update Activity</button>
    </form>

    <hr>

    <h2>Thumbnail Preview</h2>
    <img id="thumbnailImage" width="300" />

    <script>
        const form = document.getElementById('activityForm');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            let formData = new FormData(form);

            formData.append('reminder_sound', 'small');
            formData.append('reminder_vibration', '1');
            formData.append('show_in_drawer', '1');
            formData.append('notification_sound', '1');
            formData.append('notification_vibration', '1');
            formData.append('show_full_screen', '0');

            const response = await fetch(
                'https://kynetweb.com/growback/public/api/activities/2/update',
                {
                    method: 'POST',
                    body: formData
                }
            );

            const data = await response.json();

            console.log(data);

            if (data.success) {
                alert('Activity Updated Successfully');

                if (data.activity.thumbnail) {

                    document.getElementById('thumbnailImage').src =
                        'https://kynetweb.com/growback/public/storage/thumbnails/' +
                        data.activity.thumbnail;
                }
            }
        });
    </script>

</body>
</html>