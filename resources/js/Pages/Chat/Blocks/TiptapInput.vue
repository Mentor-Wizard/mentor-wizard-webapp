<script setup>
import { TransitionChild, TransitionRoot } from '@headlessui/vue';
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
import { onBeforeUnmount, ref } from 'vue';

import { useCaseChat } from '@/Pages/Chat/useCaseChat.js';

import { useCaseFileType } from '../useCaseFileType.js';

const { getColorByFileName, getIconByFileName } = useCaseFileType();
const { sendMessage, currentUser } = useCaseChat();

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
  const url = window.prompt('URL link:');
  if (!url) return;
  editor.chain().focus().setLink({ href: url }).run();
};

const buttonClass = (active) =>
  `px-2 py-1 rounded ${active ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-200'}`;

const showEmojiPicker = ref(false);
const emojis = Object.keys(emojiList);

const toggleEmojiPicker = () => {
  showEmojiPicker.value = !showEmojiPicker.value;
};

const selectEmoji = (emoji) => {
  editor.chain().focus().insertContent(emoji).run();
  showEmojiPicker.value = false;
};

const filesForm = ref([]);
const fileInput = ref(null);

const addFiles = () => fileInput.value.click();

const handleFileChange = (event) => {
  const files = event.target.files;
  if (!files) return;
  const newFiles = Array.from(files);
  filesForm.value = [...filesForm.value, ...newFiles];
  event.target.value = null;
};

const removeFile = (index) => {
  filesForm.value = filesForm.value.filter((_, i) => i !== index);
};

const send = () => {
  sendMessage(filesForm, editor.getHTML());

  editor.commands.clearContent();
  filesForm.value = [];
  showEmojiPicker.value = false;
};

onBeforeUnmount(() => {
  editor.destroy();
});
</script>

<template>
  <div
    v-if="currentUser?.canSend"
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
        <ListBulletIcon class="size-5 self-center" />
      </button>
      <button
        :class="buttonClass(editor.isActive('codeBlock'))"
        @click="toggleCodeBlock"
      >
        <CodeBracketIcon class="size-5 self-center" />
      </button>
    </div>

    <div class="relative flex items-end gap-2">
      <div
        class="max-h-[150px] min-h-[40px] flex-1 overflow-auto rounded-lg bg-white p-2"
      >
        <EditorContent :editor="editor" />
      </div>

      <button
        class="mb-2 rounded px-1 py-1 text-gray-600 hover:bg-blue-100"
        @click="toggleEmojiPicker"
      >
        <FaceSmileIcon class="size-5 cursor-pointer hover:text-blue-600" />
      </button>

      <button
        class="mb-2 rounded px-1 py-1 text-gray-600 hover:bg-blue-100"
        @click="addFiles"
      >
        <PaperClipIcon class="size-5 cursor-pointer hover:text-blue-600" />
      </button>

      <button
        class="mb-2 rounded px-1 py-1 text-gray-600 hover:bg-blue-100"
        :disabled="editor.isEmpty && filesForm.length === 0"
        @click="send"
      >
        <PaperAirplaneIcon
          class="size-5 cursor-pointer text-blue-600 hover:text-blue-800"
        />
      </button>

      <TransitionRoot :show="showEmojiPicker" as="template">
        <TransitionChild
          as="div"
          class="absolute right-0 bottom-full z-50 mb-2 origin-bottom-right"
          enter="ease-out duration-200"
          enter-from="opacity-0 scale-95"
          enter-to="opacity-100 scale-100"
          leave="ease-in duration-150"
          leave-from="opacity-100 scale-100"
          leave-to="opacity-0 scale-95"
          @click.stop
        >
          <div
            class="max-h-96 w-80 overflow-auto rounded-lg border bg-white p-3 shadow-lg ring-1 ring-black/5"
          >
            <div class="flex flex-wrap gap-1">
              <span
                v-for="(emoji, index) in emojis"
                :key="index"
                class="emoji cursor-pointer text-lg"
                @click="selectEmoji(emoji)"
              >
                {{ emoji }}
              </span>
            </div>
          </div>
        </TransitionChild>
      </TransitionRoot>

      <Teleport to="body">
        <div
          v-if="showEmojiPicker"
          class="fixed inset-0 z-40"
          @click="showEmojiPicker = false"
        />
      </Teleport>
    </div>
  </div>
  <div v-else class="text-red-600">You cannot send messages to this user.</div>

  <input
    ref="fileInput"
    type="file"
    multiple
    style="display: none"
    accept="*"
    @change="handleFileChange"
  />

  <div class="mt-2 space-y-1">
    <div
      v-for="(file, index) in filesForm"
      :key="index"
      class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-2"
    >
      <div class="flex items-center gap-2">
        <component
          :is="getIconByFileName(file.name)"
          class="h-6 w-6 flex-shrink-0 translate-y-1"
          :class="getColorByFileName(file.name)"
        />
        <p class="max-w-xs truncate text-sm text-gray-800">{{ file.name }}</p>
      </div>
      <button class="flex-shrink-0 cursor-pointer" @click="removeFile(index)">
        <TrashIcon class="h-4 w-4 text-red-400 hover:text-red-600" />
      </button>
    </div>
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

::v-deep(.tiptap ul li p) {
  display: inline-block;
  margin-left: 1.2em;
}

::v-deep(.tiptap ul li::before) {
  content: '•';
  position: absolute;
  left: 0;
  color: black;
}

::v-deep(.tiptap a) {
  color: blue;
  text-decoration: underline;
  cursor: pointer;
}

.emoji {
  font-size: 20px;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.emoji:hover {
  background-color: #f0f0f0;
}
</style>
