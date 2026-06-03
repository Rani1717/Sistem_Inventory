<div class="list-page-header list-page-header--simple">
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <a href="<?= routeTo('data-inventaris'); ?>" class="btn btn--primary btn--sm">← KEMBALI</a>
        <h1 style="margin:0;">DATA INVENTARIS SUBREG</h1>
    </div>
    <div class="list-page-header__line"></div>
</div>

<div class="category-grid category-grid--inventaris">
    <?php foreach ($data['category_cards'] as $card): ?>
        <a href="<?= e($card['route_url'] ?? routeTo('inventaris-detail')); ?>" class="category-card category-card--inventaris">
            <span class="category-card__icon">
                <i class="<?= e($card['icon']); ?>"></i>
            </span>
            <span class="category-card__label"><?= nl2br(e($card['label'])); ?></span>
            <?php if (!empty($card['sub_label'])): ?>
                <span class="category-card__sub"><?= e($card['sub_label']); ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
