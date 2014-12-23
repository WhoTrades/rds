<table class="table">
    <?foreach ($versions as $version) {?>
        <?if ($released !== null && $version['released'] != $released) {continue; } ?>
        <tr>
            <td><?=$version['id']?></td>
            <td><?=$version['name']?></td>
            <td><?=isset($version['description']) ? $version['description'] : ''?></td>
            <td><?=isset($version['releaseDate']) ? $version['releaseDate'] : ''?></td>
            <td><?=isset($version['userReleaseDate']) ? $version['userReleaseDate'] : ''?></td>
            <td><?=$version['archived'] ? 'Заархивирован' : ''?></td>
            <td><?=$version['released'] ? 'Выпущена' : ''?></td>
        </tr>
    <?}?>
</table>