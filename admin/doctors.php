<?php
/**
 * Doctor profiles — everything the public doctor pages show. Admin only.
 *
 * One screen for the list and one form for whichever doctor is being edited,
 * rather than a separate add/edit page, so reception never has to hunt for
 * where a field lives.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/booking.php';

$user   = require_login(true);
$errors = [];

const PHOTO_DIR      = __DIR__ . '/../assets/img/doctors';
const PHOTO_MAX_BYTES = 4 * 1024 * 1024;

/**
 * Turn a doctor's name into a URL slug.
 *
 * Falls back to a timestamp-free placeholder when the name has no characters
 * we can use, so the unique key still gets something to reject rather than an
 * empty string that would collide silently.
 */
function doctor_slug(string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    return $slug !== '' ? substr($slug, 0, 60) : 'doctor';
}

/**
 * Store an uploaded portrait and return its filename.
 *
 * The file is accepted only if PHP can actually read it as an image, and it is
 * saved under a name we choose with an extension we choose — never one the
 * browser supplied. assets/img/doctors/.htaccess additionally refuses to
 * execute anything in there.
 *
 * @return array{0: ?string, 1: ?string} [filename, error]
 */
function store_photo(array $file, string $slug): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'The photo did not upload. Please try again.'];
    }
    if ($file['size'] > PHOTO_MAX_BYTES) {
        return [null, 'Please use a photo under 4 MB.'];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return [null, 'That file is not an image we can read. Use JPG, PNG or WebP.'];
    }

    $ext = match ($info[2]) {
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        default        => null,
    };
    if ($ext === null) {
        return [null, 'Use a JPG, PNG or WebP photo.'];
    }

    if (!is_dir(PHOTO_DIR) && !mkdir(PHOTO_DIR, 0755, true) && !is_dir(PHOTO_DIR)) {
        return [null, 'The photo folder could not be created on the server.'];
    }

    // A random suffix means replacing a photo produces a new URL, so a browser
    // holding the old one under the site's month-long cache header still updates.
    $name = $slug . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], PHOTO_DIR . '/' . $name)) {
        return [null, 'The photo could not be saved on the server.'];
    }

    return [$name, null];
}

/** Remove a stored portrait, ignoring anything that is not a plain filename. */
function delete_photo(?string $name): void
{
    if ($name === null || $name === '' || basename($name) !== $name) {
        return;
    }
    $path = PHOTO_DIR . '/' . $name;
    if (is_file($path)) {
        @unlink($path);
    }
}

$editing = null;

if (is_post()) {
    require_csrf();
    $action = post('action');
    $id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

    if (in_array($action, ['activate', 'deactivate'], true) && $id > 0) {
        db()->prepare('UPDATE doctors SET is_active = ? WHERE id = ?')
            ->execute([$action === 'activate' ? 1 : 0, $id]);
        flash('Doctor ' . ($action === 'activate' ? 'shown on the website' : 'hidden from the website') . '.');
        redirect('doctors.php');
    }

    if ($action === 'remove_photo' && $id > 0) {
        $current = get_doctor($id);
        if ($current) {
            delete_photo($current['photo']);
            db()->prepare('UPDATE doctors SET photo = NULL WHERE id = ?')->execute([$id]);
            flash('Photo removed.');
        }
        redirect('doctors.php?edit=' . $id);
    }

    if ($action === 'save') {
        $values = [
            'name'             => post('name'),
            'qualifications'   => post('qualifications'),
            'speciality'       => post('speciality'),
            'designation'      => post('designation'),
            'languages'        => post('languages'),
            'reg_no'           => post('reg_no'),
            'location'         => post('location'),
            'opd_timings'      => post('opd_timings'),
            'bio'              => post('bio'),
            'education'        => post('education'),
            'services'         => post('services'),
            'experience_years' => post('experience_years'),
            'sort_order'       => post('sort_order', '0'),
        ];

        if (mb_strlen($values['name']) < 3 || mb_strlen($values['name']) > 120) {
            $errors['name'] = 'Please enter the doctor\'s full name.';
        }
        if ($values['qualifications'] === '' || mb_strlen($values['qualifications']) > 200) {
            $errors['qualifications'] = 'Please enter their qualifications, e.g. MBBS, MD.';
        }
        if ($values['speciality'] === '' || mb_strlen($values['speciality']) > 120) {
            $errors['speciality'] = 'Please enter their department or speciality.';
        }
        if ($values['experience_years'] !== ''
            && (!ctype_digit($values['experience_years']) || (int) $values['experience_years'] > 80)) {
            $errors['experience_years'] = 'Enter years of experience as a number up to 80.';
        }

        [$photo, $photoError] = $errors ? [null, null] : store_photo($_FILES['photo'] ?? [], doctor_slug($values['name']));
        if ($photoError !== null) {
            $errors['photo'] = $photoError;
        }

        if (!$errors) {
            $years = $values['experience_years'] === '' ? null : (int) $values['experience_years'];

            $columns = [
                'name'             => $values['name'],
                'qualifications'   => $values['qualifications'],
                'speciality'       => $values['speciality'],
                'designation'      => $values['designation'],
                'experience_years' => $years,
                'languages'        => $values['languages'],
                'reg_no'           => $values['reg_no'],
                'location'         => $values['location'],
                'opd_timings'      => $values['opd_timings'],
                'bio'              => $values['bio'],
                'education'        => $values['education'],
                'services'         => $values['services'],
                'sort_order'       => (int) $values['sort_order'],
            ];

            try {
                if ($id > 0) {
                    $existing = get_doctor($id);
                    if ($existing === null) {
                        flash('That doctor no longer exists.', 'error');
                        redirect('doctors.php');
                    }

                    if ($photo !== null) {
                        delete_photo($existing['photo']);
                        $columns['photo'] = $photo;
                    }

                    $set = implode(', ', array_map(static fn ($c) => "`{$c}` = ?", array_keys($columns)));
                    db()->prepare("UPDATE doctors SET {$set} WHERE id = ?")
                        ->execute([...array_values($columns), $id]);

                    flash('Profile saved for ' . $values['name'] . '.');
                } else {
                    $columns['slug']      = doctor_slug($values['name']);
                    $columns['photo']     = $photo;
                    $columns['is_active'] = 1;

                    $cols = implode(', ', array_map(static fn ($c) => "`{$c}`", array_keys($columns)));
                    $ph   = implode(', ', array_fill(0, count($columns), '?'));
                    db()->prepare("INSERT INTO doctors ({$cols}) VALUES ({$ph})")
                        ->execute(array_values($columns));

                    $id = (int) db()->lastInsertId();
                    flash($values['name'] . ' added. Set their OP sessions on the Schedule page.');
                }

                redirect('doctors.php?edit=' . $id);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors['name'] = 'A doctor with a very similar name already exists.';
                } else {
                    $errors['name'] = 'Could not save this profile.';
                    error_log('Doctor save failed: ' . $e->getMessage());
                }
                delete_photo($photo);
            }
        } else {
            delete_photo($photo);
        }

        // Keep what they typed on screen rather than making them start again.
        $editing = array_merge(
            ['id' => $id, 'photo' => $id > 0 ? (get_doctor($id)['photo'] ?? null) : null],
            $values
        );
    }
}

if ($editing === null) {
    $editId  = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: 0;
    $editing = $editId > 0 ? get_doctor($editId) : null;
}

$doctors = get_doctors(false);

$adminTitle    = 'Doctors';
$adminSubtitle = 'Profiles, photographs and the details shown on the website.';
$adminNav      = 'doctors';

require __DIR__ . '/_header.php';

/** Read a field from whatever is being edited, or the value just posted. */
$val = static function (string $key, string $default = '') use ($editing): string {
    return (string) ($editing[$key] ?? $default);
};
?>

<div class="panel">
  <div class="panel-head">
    <h2>Doctors on the website</h2>
    <a class="btn btn-sm btn-outline" href="doctors.php"><?= icon('plus') ?> Add a doctor</a>
  </div>
  <div class="panel-body flush">
    <div class="table-wrap">
      <table class="admin-table" style="min-width:680px">
        <thead>
          <tr>
            <th scope="col">Doctor</th>
            <th scope="col">Department</th>
            <th scope="col">Profile</th>
            <th scope="col">Status</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($doctors as $doc): ?>
            <?php
              $filled = 0;
              foreach (['designation', 'experience_years', 'languages', 'reg_no',
                        'bio', 'education', 'services', 'photo'] as $field) {
                  if (trim((string) ($doc[$field] ?? '')) !== '') {
                      $filled++;
                  }
              }
            ?>
            <tr<?= $doc['is_active'] ? '' : ' style="opacity:.55"' ?>>
              <td>
                <span class="patient-name"><?= e($doc['name']) ?></span>
                <span class="patient-meta"><?= e($doc['qualifications']) ?></span>
              </td>
              <td class="muted"><?= e($doc['speciality']) ?></td>
              <td class="muted"><?= $filled ?> of 8 details filled</td>
              <td>
                <?php if ($doc['is_active']): ?>
                  <span class="pill status-completed">Shown</span>
                <?php else: ?>
                  <span class="pill status-cancelled">Hidden</span>
                <?php endif; ?>
              </td>
              <td class="actions">
                <a class="btn-icon" href="doctors.php?edit=<?= (int) $doc['id'] ?>" title="Edit profile">
                  <?= icon('edit') ?>
                </a>
                <a class="btn-icon" href="../doctor.php?slug=<?= e($doc['slug']) ?>"
                   target="_blank" rel="noopener" title="View on the website">
                  <?= icon('arrow-right') ?>
                </a>
                <form method="post" action="doctors.php"
                      data-confirm="<?= $doc['is_active']
                          ? 'Hide this doctor from the website? Existing bookings are not affected.'
                          : 'Show this doctor on the website again?' ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="<?= $doc['is_active'] ? 'deactivate' : 'activate' ?>">
                  <input type="hidden" name="id" value="<?= (int) $doc['id'] ?>">
                  <button class="btn-icon <?= $doc['is_active'] ? 'danger' : 'good' ?>" type="submit"
                          title="<?= $doc['is_active'] ? 'Hide' : 'Show' ?>">
                    <?= icon($doc['is_active'] ? 'close' : 'check') ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="panel" style="max-width:820px">
  <div class="panel-head">
    <h2><?= $editing ? 'Edit ' . e($editing['name'] ?? 'profile') : 'Add a doctor' ?></h2>
    <?php if ($editing): ?>
      <a class="btn btn-sm btn-outline" href="doctors.php">Cancel</a>
    <?php endif; ?>
  </div>
  <div class="panel-body">
    <form method="post" action="doctors.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

      <h3 class="form-section">Who they are</h3>

      <div class="field">
        <label for="name">Full name <span class="req">*</span></label>
        <span class="hint">Shown as the page heading, e.g. Dr. Gundavarapu Venkatesh.</span>
        <input type="text" id="name" name="name" required maxlength="120" value="<?= e($val('name')) ?>">
        <?php if (isset($errors['name'])): ?><span class="error"><?= e($errors['name']) ?></span><?php endif; ?>
      </div>

      <div class="field-row cols-2">
        <div class="field">
          <label for="qualifications">Qualifications <span class="req">*</span></label>
          <span class="hint">The short line beside the name, e.g. MBBS, MD.</span>
          <input type="text" id="qualifications" name="qualifications" required maxlength="200"
                 value="<?= e($val('qualifications')) ?>">
          <?php if (isset($errors['qualifications'])): ?>
            <span class="error"><?= e($errors['qualifications']) ?></span>
          <?php endif; ?>
        </div>

        <div class="field">
          <label for="speciality">Department <span class="req">*</span></label>
          <span class="hint">e.g. General Medicine, Diabetology &amp; Endocrinology.</span>
          <input type="text" id="speciality" name="speciality" required maxlength="120"
                 value="<?= e($val('speciality')) ?>">
          <?php if (isset($errors['speciality'])): ?>
            <span class="error"><?= e($errors['speciality']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="field-row cols-2">
        <div class="field">
          <label for="designation">Designation</label>
          <span class="hint">e.g. Sr. Consultant Physician. Leave blank to show the department instead.</span>
          <input type="text" id="designation" name="designation" maxlength="160"
                 value="<?= e($val('designation')) ?>">
        </div>

        <div class="field">
          <label for="experience_years">Years of experience</label>
          <span class="hint">Leave blank to leave it off the profile.</span>
          <input type="number" id="experience_years" name="experience_years" min="0" max="80"
                 value="<?= e($val('experience_years')) ?>">
          <?php if (isset($errors['experience_years'])): ?>
            <span class="error"><?= e($errors['experience_years']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="field-row cols-2">
        <div class="field">
          <label for="languages">Languages</label>
          <span class="hint">e.g. Telugu, English, Hindi.</span>
          <input type="text" id="languages" name="languages" maxlength="160" value="<?= e($val('languages')) ?>">
        </div>

        <div class="field">
          <label for="reg_no">Medical registration number</label>
          <input type="text" id="reg_no" name="reg_no" maxlength="60" value="<?= e($val('reg_no')) ?>">
        </div>
      </div>

      <div class="field">
        <label for="photo">Photograph</label>
        <span class="hint">
          JPG, PNG or WebP, up to 4 MB. A square photo looks best — it is shown
          in a circle. Uploading a new one replaces the old.
        </span>
        <?php if (!empty($editing['photo'])): ?>
          <div class="photo-current">
            <img src="<?= e(asset('assets/img/doctors/' . $editing['photo'], '../')) ?>"
                 alt="Current photograph" width="72" height="72">
            <button class="btn btn-sm btn-outline" type="submit" name="action" value="remove_photo"
                    formnovalidate>Remove photo</button>
          </div>
        <?php endif; ?>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
        <?php if (isset($errors['photo'])): ?><span class="error"><?= e($errors['photo']) ?></span><?php endif; ?>
      </div>

      <hr>
      <h3 class="form-section">What patients read</h3>

      <div class="field">
        <label for="bio">About the doctor</label>
        <span class="hint">A paragraph shown at the top of their profile page.</span>
        <textarea id="bio" name="bio" rows="5"><?= e($val('bio')) ?></textarea>
      </div>

      <div class="field">
        <label for="education">Educational qualifications</label>
        <span class="hint">One per line. Each becomes a bullet on the profile.</span>
        <textarea id="education" name="education" rows="5"
                  placeholder="MBBS&#10;MD in General Medicine — SRM University, Chennai"><?= e($val('education')) ?></textarea>
      </div>

      <div class="field">
        <label for="services">Services offered</label>
        <span class="hint">One per line. Shown as the two-column list on the profile.</span>
        <textarea id="services" name="services" rows="8"
                  placeholder="Diabetes (Sugar) &amp; Blood Pressure&#10;Thyroid Disorders"><?= e($val('services')) ?></textarea>
      </div>

      <hr>
      <h3 class="form-section">Where and when</h3>

      <div class="field">
        <label for="opd_timings">OP timings</label>
        <span class="hint">
          Leave blank and the profile reads the real timings from the
          <a href="schedule.php">Schedule</a> page. Fill it in only to say something
          the schedule cannot, one line per entry.
        </span>
        <textarea id="opd_timings" name="opd_timings" rows="2"
                  maxlength="200"><?= e($val('opd_timings')) ?></textarea>
      </div>

      <div class="field-row cols-2">
        <div class="field">
          <label for="location">Location</label>
          <span class="hint">Leave blank to show the hospital's own address.</span>
          <input type="text" id="location" name="location" maxlength="160" value="<?= e($val('location')) ?>">
        </div>

        <div class="field">
          <label for="sort_order">Order on the website</label>
          <span class="hint">Lower numbers come first.</span>
          <input type="number" id="sort_order" name="sort_order" min="0" max="999"
                 value="<?= e($val('sort_order', '0')) ?>">
        </div>
      </div>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit">
          <?= icon('check') ?> <?= $editing && !empty($editing['id']) ? 'Save profile' : 'Add doctor' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
