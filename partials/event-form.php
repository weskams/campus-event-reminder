<?php
/**
 * Add / edit event form.
 * Expects $form, $errors, $formAction and $submitLabel.
 */

defined('APP_ROOT') || exit;
?>
<?php if ($errors): ?>
    <div class="alert alert-error" role="alert">
        <p>Please fix the following:</p>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="form event-form" method="post" action="<?= e($formAction) ?>">
    <?= csrf_field() ?>

    <div class="field">
        <label for="title">Event title</label>
        <input type="text" id="title" name="title" maxlength="150" required
               value="<?= e($form['title']) ?>">
    </div>

    <div class="field">
        <label for="description">Description <span class="optional">(optional)</span></label>
        <textarea id="description" name="description" rows="6"
                  maxlength="5000"><?= e($form['description']) ?></textarea>
    </div>

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
               value="<?= e($form['venue']) ?>">
    </div>

    <div class="field">
        <label for="category">Category</label>
        <select id="category" name="category" required>
            <?php foreach (EVENT_CATEGORIES as $option): ?>
                <option value="<?= e($option) ?>" <?= $form['category'] === $option ? 'selected' : '' ?>>
                    <?= e($option) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit"><?= e($submitLabel) ?></button>
</form>
