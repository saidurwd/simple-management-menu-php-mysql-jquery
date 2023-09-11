<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="en" class="lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="en" class="lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="en" class="lt-ie9"> <![endif]-->
<!--[if IE 9]>    <html lang="en" class="ie9"> <![endif]-->
<!--[if gt IE 9]><!-->
<html lang="en"> <!--<![endif]-->

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>Simple Management Menu</title>
    <link rel="stylesheet" type="text/css" href="./style.css">
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <div id="load"></div>
    <menu id="nestable-menu">
        <button type="button" data-action="expand-all">Expand All</button>
        <button type="button" data-action="collapse-all">Collapse All</button>
    </menu>

    <table>
        <tr>
            <td>Label</td>
            <td>:</td>
            <td><input type="text" id="label" placeholder="Fill label" required></td>
        </tr>
        <tr>
            <td>Link</td>
            <td>:</td>
            <td><input type="text" id="link" placeholder="Fill link" required></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td><button id="submit">Submit</button> <button id="reset">Reset</button></td>
        </tr>
    </table>
    <input type="hidden" id="id">
    <br /><br />

    <div class="cf nestable-lists">
        <div class="dd" id="nestable">
            <?php
            $query = $db->query("select * from menus order by ordering");
            $ref   = [];
            $items = [];

            while ($data = $query->fetch(PDO::FETCH_OBJ)) {
                $thisRef = &$ref[$data->id];
                $thisRef['parent'] = $data->parent;
                $thisRef['label'] = $data->menu_name;
                $thisRef['link'] = $data->menu_link;
                $thisRef['id'] = $data->id;
                if ($data->parent == 0) {
                    $items[$data->id] = &$thisRef;
                } else {
                    $ref[$data->parent]['child'][$data->id] = &$thisRef;
                }
            }


            function get_menu($items, $class = 'dd-list')
            {
                $html = "<ol class=\"" . $class . "\" id=\"menu-id\">";
                foreach ($items as $key => $value) {
                    $html .= '<li class="dd-item dd3-item" data-id="' . $value['id'] . '" >
                    <div class="dd-handle dd3-handle"><i class="fa-solid fa-grip-vertical"></i></div>
                    <div class="dd3-content"><i class="fa-solid fa-grip-vertical"></i><span id="label_show' . $value['id'] . '">' . $value['label'] . '</span> 
                        <span id="link_show' . $value['id'] . '">' . $value['link'] . '</span>
                        <span class="span-right"><a class="edit-button" id="' . $value['id'] . '" label="' . $value['label'] . '" link="' . $value['link'] . '" ><button type="button" class="btn btn-outline-warning btn-sm"><i class="fa fa-pencil"></i></button></a>
                            <a class="del-button" id="' . $value['id'] . '"><button type="button" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button></a></span> 
                    </div>';
                    if (array_key_exists('child', $value)) {
                        $html .= get_menu($value['child'], 'child');
                    }
                    $html .= "</li>";
                }
                $html .= "</ol>";

                return $html;
            }
            print get_menu($items);
            ?>
        </div>
    </div>
    <p></p>
    <input type="hidden" id="nestable-output">
    <button id="save">Save</button>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.js"></script>
    <script>
        $(document).ready(function() {

            var updateOutput = function(e) {
                var list = e.length ? e : $(e.target),
                    output = list.data('output');
                if (window.JSON) {
                    output.val(window.JSON.stringify(list.nestable('serialize'))); //, null, 2));
                } else {
                    output.val('JSON browser support required for this demo.');
                }
            };

            // activate Nestable for list 1
            $('#nestable').nestable({
                    group: 1
                })
                .on('change', updateOutput);



            // output initial serialised data
            updateOutput($('#nestable').data('output', $('#nestable-output')));

            $('#nestable-menu').on('click', function(e) {
                var target = $(e.target),
                    action = target.data('action');
                if (action === 'expand-all') {
                    $('.dd').nestable('expandAll');
                }
                if (action === 'collapse-all') {
                    $('.dd').nestable('collapseAll');
                }
            });


        });
    </script>

    <script>
        $(document).ready(function() {
            $("#load").hide();
            $("#submit").click(function() {
                $("#load").show();

                var dataString = {
                    label: $("#label").val(),
                    link: $("#link").val(),
                    id: $("#id").val()
                };

                $.ajax({
                    type: "POST",
                    url: "save_menu.php",
                    data: dataString,
                    dataType: "json",
                    cache: false,
                    success: function(data) {
                        if (data.type == 'add') {
                            $("#menu-id").append(data.menu);
                        } else if (data.type == 'edit') {
                            $('#label_show' + data.id).html(data.label);
                            $('#link_show' + data.id).html(data.link);
                        }
                        $('#label').val('');
                        $('#link').val('');
                        $('#id').val('');
                        $("#load").hide();
                    },
                    error: function(xhr, status, error) {
                        alert(error);
                    },
                });
            });

            $('.dd').on('change', function() {
                $("#load").show();

                var dataString = {
                    data: $("#nestable-output").val(),
                };

                $.ajax({
                    type: "POST",
                    url: "save.php",
                    data: dataString,
                    cache: false,
                    success: function(data) {
                        $("#load").hide();
                    },
                    error: function(xhr, status, error) {
                        alert(error);
                    },
                });
            });

            $("#save").click(function() {
                $("#load").show();

                var dataString = {
                    data: $("#nestable-output").val(),
                };

                $.ajax({
                    type: "POST",
                    url: "save.php",
                    data: dataString,
                    cache: false,
                    success: function(data) {
                        $("#load").hide();
                        alert('Data has been saved');

                    },
                    error: function(xhr, status, error) {
                        alert(error);
                    },
                });
            });


            $(document).on("click", ".del-button", function() {
                var x = confirm('Delete this menu?');
                var id = $(this).attr('id');
                if (x) {
                    $("#load").show();
                    $.ajax({
                        type: "POST",
                        url: "delete.php",
                        data: {
                            id: id
                        },
                        cache: false,
                        success: function(data) {
                            $("#load").hide();
                            $("li[data-id='" + id + "']").remove();
                        },
                        error: function(xhr, status, error) {
                            alert(error);
                        },
                    });
                }
            });

            $(document).on("click", ".edit-button", function() {
                var id = $(this).attr('id');
                var label = $(this).attr('label');
                var link = $(this).attr('link');
                $("#id").val(id);
                $("#label").val(label);
                $("#link").val(link);
            });

            $(document).on("click", "#reset", function() {
                $('#label').val('');
                $('#link').val('');
                $('#id').val('');
            });

        });
    </script>

</body>

</html>