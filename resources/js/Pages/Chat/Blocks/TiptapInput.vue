<script setup>
import {
  CodeBracketIcon,
  FaceSmileIcon,
  ListBulletIcon,
  PaperAirplaneIcon,
  PaperClipIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import { Placeholder } from '@tiptap/extensions';
import StarterKit from '@tiptap/starter-kit';
import { Editor, EditorContent } from '@tiptap/vue-3';
import emojiList from 'unicode-emoji-json';
import { onBeforeUnmount, onMounted, onUnmounted, ref } from 'vue';

import { useCaseFileType } from '../useCaseFileType.js';

const { getColorByFileName, getIconByFileName } = useCaseFileType();

const editor = new Editor({
  extensions: [
    StarterKit,
    Placeholder.configure({
      placeholder: 'Write something …',
    }),
  ],
  content: '',
});

const toggleBold = () => editor.chain().focus().toggleBold().run();
const toggleItalic = () => editor.chain().focus().toggleItalic().run();
const toggleBulletList = () => editor.chain().focus().toggleBulletList().run();
const toggleCodeBlock = () => editor.chain().focus().toggleCodeBlock().run();

const setLink = () => {
  if (editor.isActive('link')) {
    editor.chain().focus().unsetLink().run();
    return;
  }
  const url = window.prompt('URL посилання:');
  if (url === null || url === '') {
    return;
  }
  editor.chain().focus().setLink({ href: url }).run();
};

const buttonClass = (active) =>
  `px-2 py-1 rounded ${active ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-200'}`;

const showEmojiPicker = ref(false);
const emojis = Object.keys(emojiList);

const toggleEmojiPicker = () => {
  showEmojiPicker.value = !showEmojiPicker.value;
};

const selectEmoji = async (emoji) => {
  // Отримуємо позицію курсора
  editor.chain().focus().insertContent(emoji).run();
};

const sendMessage = () => {
  const html = editor.getHTML();
  console.log('Send message:', html);
  editor.commands.clearContent();
};

onBeforeUnmount(() => {
  editor.destroy();
});
const closeEmojiPicker = (event) => {
  const popup = document.querySelector('.emojiPopup2');
  const toggleButton = document.querySelector('.emojiToggle');

  if (
    popup
    && !popup.contains(event.target)
    && toggleButton
    && !toggleButton.contains(event.target)
  ) {
    showEmojiPicker.value = false;
  }
};

const filesForm = ref([]);

const fileInput = ref(null);
const addFiles = () => {
  fileInput.value.click();
};
const handleFileChange = (event) => {
  const files = event.target.files;
  if (!files) return;

  const newFiles = Array.from(files);
  filesForm.value = [...filesForm.value, ...newFiles];
  console.log(filesForm.value);
};
const removeFile = (index) => {
  filesForm.value = filesForm.value.filter((_, i) => i !== index);
};

onMounted(() => {
  document.addEventListener('click', closeEmojiPicker);
});

onUnmounted(() => {
  document.removeEventListener('click', closeEmojiPicker);
});
</script>

<template>
  <div
    class="flex flex-col gap-2 rounded-lg border border-gray-300 bg-gray-50 p-2"
  >
    <div class="flex gap-2 text-gray-600">
      <button :class="buttonClass(editor.isActive('bold'))" @click="toggleBold">
        B
      </button>
      <button
        :class="buttonClass(editor.isActive('italic'))"
        @click="toggleItalic"
      >
        <i>I</i>
      </button>
      <button :class="buttonClass(editor.isActive('link'))" @click="setLink">
        🔗
      </button>
      <button
        :class="buttonClass(editor.isActive('bulletList'))"
        @click="toggleBulletList"
      >
        <ListBulletIcon
          class="pointer-events-none col-start-1 row-start-1 size-5 self-center"
        />
      </button>
      <button
        :class="buttonClass(editor.isActive('codeBlock'))"
        @click="toggleCodeBlock"
      >
        <CodeBracketIcon
          class="pointer-events-none col-start-1 row-start-1 size-5 self-center"
        />
      </button>
    </div>

    <div class="flex items-end gap-2">
      <div
        class="max-h-[150px] min-h-[40px] flex-1 overflow-auto rounded-lg bg-white p-2"
      >
        <EditorContent :editor="editor" />
      </div>
      <button
        class="emojiToggle relative mb-2 rounded px-1 py-1 text-gray-600 hover:bg-blue-100"
        @click="toggleEmojiPicker"
      >
        <FaceSmileIcon
          class="size-5 cursor-pointer self-center hover:text-blue-600"
        />
        <!-- Emoji Popup -->
        <div
          v-show="showEmojiPicker"
          class="emojiPopup2 absolute right-0 bottom-full z-50 mb-2 max-h-96 w-80 overflow-auto rounded border bg-white p-3 shadow-lg"
        >
          <div class="emojiPopupContent flex flex-wrap gap-1">
            <span
              v-for="emoji in emojis"
              :key="emoji"
              class="emoji cursor-pointer text-lg"
              @click="selectEmoji(emoji)"
            >
              {{ emoji }}
            </span>
          </div>
        </div>
      </button>
      <button
        class="mb-2 rounded px-1 py-1 text-gray-600 hover:bg-blue-100"
        @click="addFiles"
      >
        <PaperClipIcon
          class="col-start-1 row-start-1 size-5 cursor-pointer self-center hover:text-blue-600"
        />
      </button>
      <button
        class="mb-2 rounded px-1 py-1 text-gray-600 hover:bg-blue-100"
        @click="sendMessage"
      >
        <PaperAirplaneIcon
          class="col-start-1 row-start-1 size-5 cursor-pointer self-center text-blue-600 hover:text-blue-800"
        />
      </button>
    </div>
  </div>

  <input
    ref="fileInput"
    type="file"
    multiple
    style="display: none"
    @change="handleFileChange"
  />

  <div
    v-for="(file, index) in filesForm"
    :key="index"
    class="flex items-center justify-between"
  >
    <div class="flex items-center">
      <component
        :is="getIconByFileName(file.name)"
        class="h-6 w-6 translate-y-1"
        :class="getColorByFileName(file.name)"
      />
      <p class="text-sm text-gray-800">{{ file.name }}</p>
    </div>
    <button class="cursor-pointer" @click="removeFile(index)">
      <TrashIcon class="h-4 w-4 text-red-400 hover:text-red-600" />
    </button>
  </div>
</template>

<style scoped>
::v-deep(.ProseMirror:focus) {
  outline: none;
}

::v-deep(.tiptap p.is-editor-empty:first-child::before) {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

::v-deep(.tiptap ul),
::v-deep(.tiptap ol) {
  padding-left: 1.5rem;
  margin-left: 1.25rem;
  list-style-position: outside;
}

::v-deep(.tiptap ul li),
::v-deep(.tiptap ol li) {
  position: relative;
  margin-left: 0.25em;
  display: block;
}

::v-deep(.tiptap ul li p)::before {
  content: '•';
  position: absolute;
  left: 0;
  color: black;
}

::v-deep(.tiptap ul li p) {
  display: block;
  margin-left: 1.2em;
}

::v-deep(.tiptap a) {
  color: blue;
  text-decoration: underline;
  cursor: pointer;
}

.emojiToggle {
  position: relative;
  cursor: pointer;
}

.emojiPopup2 {
  position: absolute;
  bottom: 35px;
  right: 0;
  z-index: 100;
}

.emojiPopupContent {
  padding: 10px;
  max-height: 200px;
  max-width: 500px;
  overflow-y: auto;
  display: flex;
  flex-wrap: wrap;
  gap: 0;
}

.emoji {
  font-size: 20px;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  transition: background-color 0.2s;

  &:hover {
    background-color: #f0f0f0;
  }
}
</style>
