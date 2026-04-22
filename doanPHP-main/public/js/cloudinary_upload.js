document.addEventListener('DOMContentLoaded', () => {
  const btnSelect     = document.getElementById('btn-select-media');
  const btnClear      = document.getElementById('btn-clear-media');
  const fileInput     = document.getElementById('post-media-file');
  const previewBox    = document.getElementById('media-preview');
  const mediaUrlInput = document.getElementById('media_url');
  const mediaTypeInput= document.getElementById('media_type');
  const mediaPidInput = document.getElementById('media_public_id');
  const form          = document.getElementById('create-post-form');

  const cloudName = window.CLOUDINARY_CLOUD_NAME;
  const preset    = window.CLOUDINARY_UNSIGNED_PRESET;

  const MAX_SIZE  = 25 * 1024 * 1024; // 25MB tùy chỉnh
  const ACCEPT    = [
    'image/jpeg','image/png','image/webp','image/gif',
    'video/mp4','video/webm','video/quicktime','video/x-matroska'
  ];

  function openPicker() {
    fileInput.value = '';
    fileInput.click();
  }

  function validateFile(file) {
    if (!file) return 'Không có file';
    if (!ACCEPT.includes(file.type)) return 'Định dạng không hỗ trợ (' + file.type + ')';
    if (file.size > MAX_SIZE) return 'File quá lớn (> ' + (MAX_SIZE/1024/1024) + 'MB)';
    return '';
  }

  function showPreviewLocal(file) {
    previewBox.style.display = 'block';
    previewBox.innerHTML = '';
    const typeGroup = file.type.startsWith('video') ? 'video' : 'image';
    const reader = new FileReader();
    reader.onload = e => {
      if (typeGroup === 'video') {
        const v = document.createElement('video');
        v.controls = true;
        v.style.width = '100%';
        v.style.borderRadius = '12px';
        v.src = e.target.result;
        previewBox.appendChild(v);
      } else {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.style.maxWidth = '100%';
        img.style.borderRadius = '12px';
        previewBox.appendChild(img);
      }
    };
    reader.readAsDataURL(file);
  }

  async function uploadToCloudinary(file) {
    if (!cloudName || !preset) {
      throw new Error('Thiếu cấu hình Cloudinary');
    }
    const fd = new FormData();
    fd.append('file', file);
    fd.append('upload_preset', preset);

    const endpoint = file.type.startsWith('video')
      ? `https://api.cloudinary.com/v1_1/${cloudName}/video/upload`
      : `https://api.cloudinary.com/v1_1/${cloudName}/image/upload`;

    const res = await fetch(endpoint, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.error) {
      throw new Error(data.error.message || 'Upload thất bại');
    }
    return data; // contains secure_url, public_id, resource_type, format,...
  }

  function setButtonLoading(btn, isLoading, labelLoading='Đang upload...', labelNormal='Chọn media') {
    if (!btn) return;
    if (isLoading) {
      btn.disabled = true;
      btn.classList.add('loading');
      btn.dataset.originalText = btn.textContent;
      btn.textContent = labelLoading;
    } else {
      btn.disabled = false;
      btn.classList.remove('loading');
      btn.textContent = labelNormal;
    }
  }

  btnSelect?.addEventListener('click', openPicker);

  fileInput?.addEventListener('change', async () => {
    const file = fileInput.files?.[0];
    if (!file) return;

    const err = validateFile(file);
    if (err) {
      alert(err);
      return;
    }

    // Preview local ngay để user thấy
    showPreviewLocal(file);
    setButtonLoading(btnSelect, true);

    try {
      const data = await uploadToCloudinary(file);

      // Loại media
      let mType;
      if (data.resource_type === 'video') {
        mType = 'video';
      } else if (data.format === 'gif') {
        mType = 'gif';
      } else {
        mType = 'image';
      }

      // Ghi hidden fields
      mediaUrlInput.value  = data.secure_url;
      mediaTypeInput.value = mType;
      if (mediaPidInput) mediaPidInput.value = data.public_id;

      // Đổi preview sang file trên cloud (optional: để xem transform URL)
      previewBox.innerHTML = '';
      if (mType === 'video') {
        const v = document.createElement('video');
        v.controls = true;
        v.style.width = '100%';
        v.style.borderRadius = '12px';
        v.src = data.secure_url;
        previewBox.appendChild(v);
      } else {
        const img = document.createElement('img');
        img.src = data.secure_url;
        img.style.maxWidth = '100%';
        img.style.borderRadius = '12px';
        previewBox.appendChild(img);
      }

      btnClear.style.display = 'inline-block';
    } catch(e) {
      alert('Upload lỗi: ' + e.message);
      // reset preview & fields
      previewBox.style.display = 'none';
      previewBox.innerHTML = '';
      mediaUrlInput.value = '';
      mediaTypeInput.value = '';
      if (mediaPidInput) mediaPidInput.value = '';
    } finally {
      setButtonLoading(btnSelect, false);
    }
  });

  btnClear?.addEventListener('click', () => {
    // Xóa media đã chọn
    mediaUrlInput.value = '';
    mediaTypeInput.value = '';
    if (mediaPidInput) mediaPidInput.value = '';
    previewBox.innerHTML = '';
    previewBox.style.display = 'none';
    fileInput.value = '';
    btnClear.style.display = 'none';
    btnSelect.textContent = 'Chọn media';
  });

  // Submit form
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd  = new FormData(form);
    const submitBtn = form.querySelector('button[type=submit]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Đang đăng...';

    try {
      const res  = await fetch('/public/actions/create_post.php', { method:'POST', body: fd });
      // Đọc raw để debug nếu cần
      const raw = await res.text();
      let data;
      try { data = JSON.parse(raw); } catch { data = { error: 'Phản hồi không phải JSON', raw }; }
      if (data.error) {
        alert(data.error);
        submitBtn.disabled = false;
        submitBtn.textContent = 'Đăng';
        console.log('Raw response:', raw);
      } else {
        location.reload();
      }
    } catch(err) {
      alert('Lỗi mạng: ' + err.message);
      submitBtn.disabled = false;
      submitBtn.textContent = 'Đăng';
    }
  });
});