<?php $this->printGoBackUrl($this->url(array('action'=>'index'))); ?>

<div class="box grid_8 round_all">
    <h2 class="box_head grad_colour">Tambah Fasilitas Dermaga</h2>
    <div class="toggle_container">
        <div class="block">
            <?php
            $this->form->setAction($this->url());
            echo $this->form;
            ?>
        </div>
    </div>
</div>
