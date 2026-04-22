(() => {
  const cloudName = window.CLOUDINARY_CLOUD_NAME || '';
  const preset    = window.CLOUDINARY_UNSIGNED_PRESET || '';
  const MAX_SIZE  = 5 * 1024 * 1024;
  const ACCEPT    = ['image/jpeg','image/png','image/webp','image/gif'];

  // Lấy phần tử
  const avatarFile     = document.getElementById('avatarFile');
  const coverFile      = document.getElementById('coverFile');
  const avatarPreview  = document.getElementById('avatarPreview');
  const coverPreview   = document.getElementById('coverPreview');
  const avatarPidInput = document.getElementById('profile_picture_public_id');
  const coverPidInput  = document.getElementById('cover_photo_public_id');
  const btnAvatar      = document.getElementById('btnSelectAvatar');
  const btnCover       = document.getElementById('btnSelectCover');
  const btnClearAvatar = document.getElementById('btnClearAvatar');
  const btnClearCover  = document.getElementById('btnClearCover');
  const btnSave        = document.getElementById('btnSave');
  const saveState      = document.getElementById('saveState');
  const form           = document.getElementById('form-edit-profile');

  // Lưu tình trạng ban đầu
  const originalAvatarPid = avatarPidInput?.value || '';
  const originalCoverPid  = coverPidInput?.value || '';

  // Lưu URL gốc đang hiển thị (được render từ PHP)
  const originalAvatarUrl = avatarPreview?.style.backgroundImage || '';
  const originalCoverUrl  = coverPreview?.style.backgroundImage || '';


  function buildCloudUrl(publicId, type='avatar', w=140, h=140){
    if (!cloudName) return '/public/img/default_avatar.jpg';
    const pid = publicId || (type === 'avatar' ? defaultAvatarPublicId : defaultCoverPublicId);
    const ver = (type === 'cover' && defaultCoverVersion && !publicId) ? `/v${defaultCoverVersion}` : '';
    return `https://res.cloudinary.com/${cloudName}/image/upload/c_fill,w_${w},h_${h},q_auto,f_auto${ver}/${encodeURIComponent(pid)}.jpg`;
  }

  function validate(file){
    if(!file) return 'Không có file';
    if(!ACCEPT.includes(file.type)) return 'Định dạng không hỗ trợ';
    if(file.size > MAX_SIZE) return 'File > 5MB';
    return '';
  }

  async function upload(file){
    const err = validate(file);
    if(err) throw new Error(err);
    if(!cloudName || !preset) throw new Error('Thiếu cloudName hoặc uploadPreset');
    const fd = new FormData();
    fd.append('file', file);
    fd.append('upload_preset', preset);
    const res = await fetch(`https://api.cloudinary.com/v1_1/${cloudName}/image/upload`, {
      method:'POST',
      body:fd
    });
    const data = await res.json();
    if (data.error) throw new Error(data.error.message || 'Upload thất bại');
    return data;
  }

  function previewLocal(file, previewEl){
    const reader = new FileReader();
    reader.onload = e => previewEl.style.backgroundImage = `url('${e.target.result}')`;
    reader.readAsDataURL(file);
  }

  function attachPicker(btn, input){
    btn?.addEventListener('click', () => { input.value=''; input.click(); });
  }
  attachPicker(btnAvatar, avatarFile);
  attachPicker(btnCover, coverFile);

  async function handleAvatar(){
    const file = avatarFile.files?.[0];
    if(!file) return;
    previewLocal(file, avatarPreview);
    btnAvatar.disabled = true; btnAvatar.textContent = 'Upload...';
    try {
      const up = await upload(file);
      avatarPidInput.value = up.public_id;
      avatarPreview.style.backgroundImage = `url('${up.secure_url}')`;
      btnAvatar.textContent = 'Đã chọn';
    } catch(e){
      alert('Avatar: ' + e.message);
      // Quay lại avatar gốc nếu upload fail
      avatarPreview.style.backgroundImage = originalAvatarUrl || buildCloudUrl(originalAvatarPid, 'avatar');
      avatarPidInput.value = originalAvatarPid;
      btnAvatar.textContent = 'Chọn lại';
    } finally {
      btnAvatar.disabled = false;
    }
  }

  async function handleCover(){
    const file = coverFile.files?.[0];
    if(!file) return;
    previewLocal(file, coverPreview);
    btnCover.disabled = true; btnCover.textContent = 'Upload...';
    try {
      const up = await upload(file);
      coverPidInput.value = up.public_id;
      coverPreview.style.backgroundImage = `url('${up.secure_url}')`;
      btnCover.textContent = 'Đã chọn';
    } catch(e){
      alert('Cover: ' + e.message);
      coverPreview.style.backgroundImage = originalCoverUrl || buildCloudUrl(originalCoverPid,'cover',1200,380);
      coverPidInput.value = originalCoverPid;
      btnCover.textContent = 'Chọn lại';
    } finally {
      btnCover.disabled = false;
    }
  }

  avatarFile?.addEventListener('change', handleAvatar);
  coverFile?.addEventListener('change', handleCover);

  // Nút Xóa (
  btnClearAvatar?.addEventListener('click', () => {
    avatarPidInput.value = originalAvatarPid;
    avatarPreview.style.backgroundImage = originalAvatarUrl || `url('${buildCloudUrl(originalAvatarPid,'avatar')}')`;
  });

  btnClearCover?.addEventListener('click', () => {
   
    coverPidInput.value = originalCoverPid;
    coverPreview.style.backgroundImage = originalCoverUrl || `url('${buildCloudUrl(originalCoverPid,'cover',1200,380)}')`;
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    btnSave.disabled = true;
    saveState.style.display = 'inline';
    try {
      const fd = new FormData(form);
      const resp = await fetch('/public/actions/update_profile.php', { method:'POST', body: fd });
      const data = await resp.json().catch(() => ({ error:'Phản hồi không phải JSON' }));
      if (data.error){
        alert(data.error);
      } else {
        // alert('Đã lưu!');
        location.href = '/public/profile.php';
      }
    } catch(err){
      alert('Lỗi mạng: ' + err.message);
    } finally {
      btnSave.disabled = false;
      saveState.style.display = 'none';
    }
  });
})();