<?php
    session_start();

    if (!isset($_SESSION["user_id"])) {
        header("location: index.php");
        exit();
    }

    require "includes/_config.php";

    $user_id = $_SESSION["user_id"];

    // Handle Add todo functionality
    if (($_SERVER["REQUEST_METHOD"] == "POST") && (isset($_POST["add_task"]))) {
        $title = $_POST["title"];
        $description = $_POST["desc"];
        $due_date = $_POST["due_date"];

        $stmt = $conn->prepare("INSERT INTO todos (user_id, todo_title, todo_description, due_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $description, $due_date);
        $stmt->execute();
        $stmt->close();
    }

    // Handle status change functionality
    if(($_SERVER["REQUEST_METHOD"] == "POST") && (isset($_POST["current_status"]))) {
        $id = $_POST["todo_id"];
        $new_status = $_POST["current_status"] === "pending" ? "completed": "pending";
        
        $stmt = $conn->prepare("UPDATE todos SET `status`= ? WHERE todo_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $new_status, $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    //Handle delete functionality
    if(($_SERVER["REQUEST_METHOD"] == "POST") && (isset($_POST["delete_todo"]))) {
        $id = $_POST["todo_id"];
        
        $stmt = $conn->prepare("DELETE FROM `todos` WHERE `todos`.`todo_id` = ? AND `todos`.`user_id` = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Handle update functionality
    if(($_SERVER["REQUEST_METHOD"] == "POST") && (isset($_POST["update_todo"]))) {
        $id = $_POST["todo_id"];
        $title = $_POST["title"];
        $description = $_POST["desc"];
        $due_date = $_POST["due_date"];
        
        $stmt = $conn->prepare("UPDATE `todos` SET `todo_title` = ?, `todo_description` = ?, `due_date` = ? WHERE `todos`.`todo_id` = ? AND `todos`.`user_id` = ?;");
        $stmt->bind_param("sssii", $title, $description, $due_date, $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Search and filter functionality
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';

    $query = "SELECT * FROM todos WHERE user_id = ?";

    if (!empty($search)) {
        $query .= " AND (`todo_title` LIKE '%$search%' OR `todo_description` LIKE '%$search%')";
    }
    if ($status !== 'all') {
        $query .= " AND status = '$status'";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | TodoList - App</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <?php include "includes/_header.php"; ?>

    <div class="container mt-5">

        <div class="container">
            <h2 class="text-center">Create Your Todo</h2>

            <form method="POST" action="dashboard.php" class="mb-3">
                <input type="text" name="title" placeholder="Task Title" required class="form-control mb-2">
                <textarea name="desc" placeholder="Task Description" class="form-control mb-2"></textarea>
                <input type="date" name="due_date" class="form-control w-auto mb-2">
                <button type="submit" name="add_task" class="btn btn-primary">Add Task</button>
            </form>
        </div>

        <div class="container mt-3">

            <h2 class="mb-3">Your Tasks</h2>

             <!-- Search and Filter Section -->
             <form method="GET" id="filterForm" action="dashboard.php" class="d-flex gap-2 mb-3">
                <input type="text" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>" 
                    placeholder="Search tasks" class="form-control">
                <select name="status" id="statusSelect" class="form-select">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <button type="button" class="btn btn-light" id="resetBtn">Reset</button>
            </form>

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($todo = $result->fetch_assoc()) :  ?>

                        <tr>
                            <td><?php echo htmlspecialchars($todo['todo_title']); ?></td>
                            <td><?php echo htmlspecialchars($todo['todo_description']); ?></td>
                            <td><?php echo htmlspecialchars($todo['due_date']); ?></td>

                            <td>
                                <form method="POST" action="dashboard.php" style="display:inline;">
                                    <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $todo['status']; ?>">
                                    <input type="checkbox"
                                           name="isCompleted"
                                           onchange="this.form.submit()" 
                                           <?php echo $todo['status'] == "completed" ? 'checked' : ''; ?>
                                           
                                    >
                                    <?php echo $todo['status'] === "completed" ? 'Completed' : 'Pending'; ?>
                                </form>
                            </td>

                            <td>
                                <div class="d-flex gap-3 align-items-start">
                                    <button type="button" class="btn btn-warning edit-btn" data-id="<?php echo $todo['todo_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                        
                                    <form method="POST" class="ms-3" action="dashboard.php"
                                          class="edit-form" id="edit-form-<?php echo $todo['todo_id']; ?>"
                                          style="display: none; flex: 1;">

                                        <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">

                                        <input type="text" 
                                            name="title" 
                                            value="<?php echo htmlspecialchars($todo['todo_title']); ?>" 
                                            class="form-control mb-2"
                                        >

                                        <textarea name="desc"  
                                                class="form-control mb-2"> <?php echo htmlspecialchars($todo['todo_description']); ?>
                                        </textarea>

                                        <input type="date" 
                                                name="due_date" 
                                                value="<?php echo htmlspecialchars($todo['due_date']); ?>"
                                                class="form-control w-auto mb-2"
                                        >

                                        <button type="submit" name="update_todo" class="btn btn-success">Update</button>
                                    </form>
                            

                                    <form method="POST" action="dashboard.php" onsubmit="return handleDelete();">
                                        <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                        <button type="submit" id="delete" name="delete_todo" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr> 
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('resetBtn').addEventListener('click', function () {
            // Clear the search input and reset the status dropdown
            document.getElementById('searchInput').value = '';
            document.getElementById('statusSelect').value = 'all';

            // Submit the form to reload the page with cleared filters
            document.getElementById('filterForm').submit();
        });

        const editButtons = document.querySelectorAll(".edit-btn");

        editButtons.forEach(button => {
            
            button.addEventListener("click", function() {
                const todoId = this.getAttribute(`data-id`);
                const editForm = document.getElementById(`edit-form-${todoId}`);

                if(editForm.style.display === 'none') {
                    editForm.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-times"></i>';
                } else {
                    editForm.style.display = 'none';
                    this.innerHTML = '<i class="fas fa-edit"></i>'; 
                }
            });
        });

      function handleDelete() {
        return confirm("Are you sure you want to delete this todo?");
      }
    </script>

</body>

</html>