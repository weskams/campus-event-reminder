<?php
/**
 * Add / edit event form.
 * Expects $form, $errors, $formAction and $submitLabel.
 */

defined('APP_ROOT') || exit;
?>
<div class="form-layout">
    <form class="card form-card" method="post" action="<?= e($formAction) ?>" data-submit-once>
        <?= csrf_field() ?>

        <?php if ($errors): ?>
            <div class="alert alert-error" role="alert">
                <?= icon('alert', 'alert-icon') ?>
                <div>
                    <p><strong>Please fix the following:</strong></p>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <fieldset class="form-section">
            <legend>The basics</legend>

            <div class="field">
                <label for="title">Event title</label>
                <input type="text" id="title" name="title" maxlength="150" required
                       placeholder="e.g. Tech Innovation Conference"
                       value="<?= e($form['title']) ?>">
            </div>

            <div class="field">
                <label for="description">Description <span class="optional">Optional</span></label>
                <textarea id="description" name="description" rows="6" maxlength="5000"
                          placeholder="What should students expect? Speakers, agenda, what to bring…"><?= e($form['description']) ?></textarea>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>When and where</legend>

            <div class="field-row">
                <div class="field">
                    <label for="event_date">Date</label>
                    <input type="date" id="event_date" name="event_date" required
                           min="<?= e(date('Y-m-d')) ?>"
                           value="<?= e($form['event_date']) ?>">
                </div>

                <div class="field">
                    <label for="event_time">Start time</label>
                    <input type="time" id="event_time" name="event_time" required
                           value="<?= e($form['event_time']) ?>">
                </div>
            </div>

            <div class="field">
                <label for="venue">Venue</label>
                <input type="text" id="venue" name="venue" maxlength="150" required
                       placeholder="e.g. ICT Centre Auditorium"
                       value="<?= e($form['venue']) ?>">
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Category</legend>

            <div class="choice-grid">
                <?php foreach (EVENT_CATEGORIES as $option): ?>
                    <label class="choice cat-<?= e(category_slug($option)) ?>">
                        <input type="radio" name="category" value="<?= e($option) ?>" required
                               <?= $form['category'] === $option ? 'checked' : '' ?>>
                        <span class="choice-swatch" aria-hidden="true"></span>
                        <span class="choice-label"><?= e($option) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="form-actions">
            <button type="submit" class="button button-lg"><?= e($submitLabel) ?> <?= icon('arrow-right') ?></button>
        </div>
    </form>

    <aside class="form-tips">
        <div class="card card-soft">
            <h2><?= icon('sparkle') ?> Make it shine</h2>
            <ul class="tip-list">
                <li><strong>Lead with the hook.</strong> Titles like "Career Fair: 30+ employers hiring" get more sign-ups than "Career Fair".</li>
                <li><strong>Be specific about the venue.</strong> Include the building and room so nobody gets lost.</li>
                <li><strong>Say who it's for.</strong> First years? Final-year projects? Everyone welcome?</li>
                <li><strong>Reminders are automatic.</strong> Registered students are reminded an hour before, and again if you change the time.</li>
            </ul>
        </div>
    </aside>
</div>
