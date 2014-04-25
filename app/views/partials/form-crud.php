<?php $f = $this->element; ?>

<form method="post" action="<?php echo $f->getAction(); ?>" enctype="<?php echo $f->getEnctype(); ?>">
    <?php foreach ($f->getElements() as $el) : ?>
        <?php if ($el->getType() != 'Zend_Form_Element_Submit') : ?>
            <label><?php echo $el->getLabel(); ?></label>
            <div class="input_group	">
                <?php if ($el->getType() === 'Zend_Form_Element_File') : ?>
                    <?php echo $el->renderFile(); ?>
                <?php else : ?>
                    <?php echo $el->renderViewHelper(); ?>
                <?php endif; ?>
                <?php echo $el->renderErrors(); ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <button class="button_colour round_all">
        <span><?php echo $f->submit->getLabel(); ?></span>
    </button>
</form>