<!DOCTYPE html>
<html>

<head>
    <title>Task Manager</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
        }

        .container {
            width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 15px;
        }

        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            margin-right: 5px;
        }

        .delete-btn {
            background: red;
        }

        .edit-btn {
            background: green;
        }

        .task-details {
            background: #e0f7fa;
            padding: 15px;
            margin-top: 20px;
            border-radius: 6px;
        }
    </style>

</head>

<body>

    <div class="container">

        <h2>Task Manager</h2>

        <form id="taskForm">

            <label>Email</label>
            <input type="email" name="email" placeholder="Enter Email" required>

            <label>Title</label>
            <input type="text" name="title" placeholder="Enter task title">

            <label>Description</label>
            <textarea name="description" placeholder="Enter description"></textarea>

            <label>Category</label>
            <select name="category">
                <option value="">Select Category</option>
                <option value="selfcare">Self Care</option>
                <option value="personal">Personal</option>
                <option value="professional">Professional</option>
            </select>

            <label>Start Time</label>
            <input type="time" name="start_time">

            <label>End Time</label>
            <input type="time" name="end_time">

            <button type="submit">Create Task</button>

        </form>

        <div id="taskResult"></div>

    </div>

    <script>
        let currentEmail = null;


        // CREATE TASK
        document.getElementById("taskForm").addEventListener("submit", function(e) {

            e.preventDefault();

            let form = document.getElementById("taskForm");
            let formData = new FormData(form);

            fetch("/api/tasks", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {

                    if (data.status) {

                        currentEmail = data.task.email;

                        let div = document.getElementById("taskResult");

                        div.innerHTML = `
<div class="task-details">

<strong>Email:</strong> ${data.task.email} <br>
<strong>Title:</strong> ${data.task.title} <br>
<strong>Description:</strong> ${data.task.description} <br>
<strong>Category:</strong> ${data.task.category} <br>
<strong>Start Time:</strong> ${data.task.start_time} <br>
<strong>End Time:</strong> ${data.task.end_time} <br><br>

<button class="edit-btn" onclick="editTask()">Edit</button>
<button class="delete-btn" onclick="deleteTask()">Delete</button>

</div>
`;

                        form.reset();

                    } else {

                        alert(data.message);

                    }

                });

        });


        // DELETE TASK
        function deleteTask() {

            fetch("/api/tasks/" + currentEmail, {
                    method: "DELETE"
                })
                .then(res => res.json())
                .then(data => {

                    alert(data.message);

                    document.getElementById("taskResult").innerHTML = "";

                });

        }

        // edit TASK
        function editTask() {

            let title = prompt("Enter new title");
            let description = prompt("Enter new description");
            let start_time = prompt("Enter new start time (HH:MM)");
            let end_time = prompt("Enter new end time (HH:MM)");

            fetch("/api/tasks/" + currentEmail, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        title: title,
                        description: description,
                        start_time: start_time,
                        end_time: end_time
                    })
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                });
        }
    </script>

</body>

</html>