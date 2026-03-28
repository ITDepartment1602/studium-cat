<?php
include('conn/conn.php');
?>

<!-- POST -->
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_GET["id"];

    if (isset($_POST["note_title"]) && isset($_POST["note_content"])) {
        $noteTitle = $_POST["note_title"];
        $noteContent = $_POST["note_content"];
        $dateTime = date("Y-m-d H:i:s");

        try {
            $stmt = $conn->prepare("INSERT INTO tbl_notes (login_id, note_title, note, date_time) VALUES (:id, :note_title, :note, :date_time)");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':note_title', $noteTitle);
            $stmt->bindParam(':note', $noteContent);
            $stmt->bindParam(':date_time', $dateTime);
            $stmt->execute();
            echo '<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>';
            echo '<script>
            swal({
                title: "Success!",
                text: "Note added successfully!",
                icon: "success",
                button: "Ok",
            }).then(function() {
              
            });
            </script>';
        } catch (PDOException $e) {
            // Handle the exception (optional: log the error)
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take-Note App</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />


    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #f5f7ff;
        }

        .main-panel,
        .card {
            margin: auto;
            height: 90vh;
            overflow-y: auto;
        }

        .note-content {
            max-height: 20em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .modal-content {
            width: 50%;
            margin-left: 25%;
        }

        @media (max-width: 768px) {
            .shownotes {
                display: none;
            }

            .modal-content {
                width: 90%;
                margin-left: 4%;
            }
        }
    </style>

</head>

<body>
    <!-- Button trigger modal -->
    <a data-toggle="modal" data-target="#exampleModalCenter" class="sidebar-link" style="cursor: pointer;">
        <i class="fa fa-book" style="cursor: pointer;" title="My Notes"></i>
    </a>

    <!-- Modal -->
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:100%; margin-top: -5%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle" style="color: #0A2558;"><b>My Notes</b></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">

                        <!-- Add Note -->
                        <div class="col-md-4 border-right">
                            <div class="card" style="height: 100%;">
                                <div class="card-header" style="color: #0A2558;">
                                    Add Note
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group">
                                            <label for="noteTitle" style="color: #0A2558;">Title</label>
                                            <input type="text" class="form-control" id="noteTitle" name="note_title"
                                                placeholder="Title">
                                            <small id="emailHelp" class="form-text text-muted">Title of your
                                                note</small>
                                            <input type="hidden" name="login_id" value=" ">
                                        </div>
                                        <div class="form-group">
                                            <label for="note" style="color: #0A2558;">Note</label>
                                            <textarea class="form-control" id="note" name="note_content"
                                                rows="7"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-secondary"
                                            style="float: right; background-color: #1B4965;">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>




                        <!-- Update and Delete Notes -->
                        <div class="col-md-8">
                            <div class="shownotes">
                                <div class="card" style="height: 490px;">
                                    <div class="card-header" style="color: #0A2558;">
                                        Notes Details
                                    </div>

                                    <div class="card-body">
                                        <div class="data-item">
                                            <ul class="list-group">

                                                <?php
                                                $id = $_GET['id'];
                                                $stmt = $conn->prepare("SELECT * FROM `tbl_notes` WHERE login_id = :id");
                                                $stmt->bindParam(':id', $id);
                                                $stmt->execute();

                                                $result = $stmt->fetchAll();

                                                foreach ($result as $row) {
                                                    $noteID = $row['tbl_notes_id'];
                                                    $noteTitle = $row['note_title'];
                                                    $noteContent = $row['note'];
                                                    $noteDateTime = $row['date_time'];

                                                    // Convert the date_time value to a formatted date and time string
                                                    $formattedDateTime = date('F j, Y H:i A', strtotime($noteDateTime));
                                                    ?>
                                                    <li class="list-group-item mt-2" style="color: #000;">
                                                        <h5 style="text-transform:uppercase;">
                                                            <b><?php echo $noteTitle ?></b>
                                                        </h5>
                                                        <p class="note-content"><?php echo $noteContent ?></p>
                                                        <small class="block text-muted text-info">Created:
                                                            <?php echo $formattedDateTime ?></small>
                                                        <div style="display: flex; justify-content: end;">
                                                            <a style="color: #1B4965; text-decoration: underline; cursor: pointer;"
                                                                onclick="showFullNote('<?php echo addslashes($noteTitle); ?>', '<?php echo addslashes($noteContent); ?>')">
                                                                View Note
                                                            </a>
                                                        </div>
                                                        <script>
                                                            function showFullNote(title, content) {
                                                                Swal.fire({
                                                                    title: title,
                                                                    html: `<p style="text-align: left;">${content}</p>`,
                                                                    showCloseButton: false,
                                                                    showCancelButton: false,
                                                                    showConfirmButton: false,
                                                                    customClass: {
                                                                        popup: 'swal2-note-popup'
                                                                    }
                                                                });
                                                            }
                                                        </script>
                                                    </li>
                                                    <?php
                                                }
                                                ?>

                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function delete_note(id) {
            if (confirm("Do you confirm to delete this note?")) {
                window.location = "../delete_note.php?delete=" + id;
            }
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
        integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"
        integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"
        integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
        crossorigin="anonymous"></script>
</body>

</html>