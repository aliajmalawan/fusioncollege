<?php if (empty(getLeadershipMessages())) { return; } ?>
<!-- Leadership Messages Section ($leadership_excerpt: the homepage shows the opening, About the full message) -->
<section id="leadership">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Leadership</span>
            <h2>Message from Our Leadership</h2>
            <p>Words of wisdom from our esteemed leaders</p>
        </div>

        <?php
        $leaders = getLeadershipMessages();
        $count = 0;
        foreach ($leaders as $leader):
            $count++;
            // Every second message puts the photo on the right (desktop only; on small screens the photo always comes first)
            $is_reverse = ($count % 2 == 0);
        ?>

        <!-- Leadership Message: <?php echo htmlspecialchars($leader['name']); ?> -->
        <div class="leader-card reveal<?php echo $is_reverse ? ' leader-card-reverse' : ''; ?>">
            <div class="leader-profile">
                <div class="leader-photo">
                    <?php if (!empty($leader['photo'])): ?>
                        <img src="<?php echo htmlspecialchars(mediaThumb($leader['photo'], 640)['src']); ?>" alt="<?php echo htmlspecialchars($leader['name']); ?>" loading="lazy" onerror="this.remove()">
                    <?php endif; ?>
                    <i class="fas fa-user-tie" aria-hidden="true"></i>
                </div>
                <h3><?php echo htmlspecialchars($leader['role_title'] ?? 'Leadership Message'); ?></h3>
                <p class="leader-name"><?php echo htmlspecialchars($leader['name']); ?></p>
                <p class="leader-designation"><?php echo htmlspecialchars($leader['designation']); ?></p>
            </div>

            <div class="leader-message">
                <i class="fas fa-quote-left leader-quote" aria-hidden="true"></i>
                <?php
                $leader_paragraphs = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', (string) $leader['message']))));
                $leader_short = !empty($leadership_excerpt) && count($leader_paragraphs) > 1;
                ?>
                <div class="leader-text"><?php echo nl2br(htmlspecialchars($leader_short ? $leader_paragraphs[0] : $leader['message'])); ?></div>
                <?php if ($leader_short): ?>
                <a href="about#leadership" class="level-link leader-more">Read the full message <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                <?php endif; ?>
                <div class="leader-sign">
                    <?php if (!empty($leader['signature'])): ?>
                        <img src="<?php echo htmlspecialchars($leader['signature']); ?>" alt="Signature" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <p class="leader-sign-name"><?php echo htmlspecialchars($leader['name']); ?></p>
                    <p class="leader-designation"><?php echo htmlspecialchars($leader['designation']); ?></p>
                </div>
            </div>
        </div>

        <?php endforeach; ?>
    </div>
</section>
