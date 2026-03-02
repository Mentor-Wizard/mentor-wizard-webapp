<script setup>
import { computed } from 'vue';

import { useCaseChat } from '@/Pages/Chat/useCaseChat.js';

import { useCaseFileType } from '../useCaseFileType.js';

const { getColorByFileName, getIconByFileName, formatFileSize } =
  useCaseFileType();

const { chatMessages, scrollContainer } = useCaseChat();

const getDayLabel = (dateString) => {
  const date = new Date(dateString);
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(today.getDate() - 1);

  const d = date.toLocaleDateString();
  const t = today.toLocaleDateString();
  const y = yesterday.toLocaleDateString();

  if (d === t) {
    return 'Today';
  } else if (d === y) {
    return 'Yesterday';
  } else {
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  }
};

const groupedMessages = computed(() => {
  const groups = new Map();

  for (const message of chatMessages.value) {
    const dateKey = new Date(message.timestamp).toLocaleDateString();

    if (!groups.has(dateKey)) {
      groups.set(dateKey, {
        dateLabel: getDayLabel(message.timestamp),
        messages: [],
      });
    }
    groups.get(dateKey).messages.push(message);
  }

  return Array.from(groups.values());
});
</script>

<template>
  <div
    ref="scrollContainer"
    class="mx-auto h-full w-full max-w-lg space-y-4 overflow-y-auto bg-gray-50 px-4 pt-0 pb-4"
  >
    <div
      v-for="(group, index) in groupedMessages"
      :key="index"
      class="relative"
    >
      <div class="sticky top-0 z-10 bg-gray-50 py-0 text-center">
        <span
          class="my-2 inline-block rounded-full bg-gray-200 px-3 py-1 text-xs text-gray-600 shadow-md"
        >
          {{ group.dateLabel }}
        </span>
      </div>
      <div
        v-for="msg in group.messages"
        :key="msg.id"
        :class="[
          'my-2 flex',
          msg.sender === 'user' ? 'justify-end' : 'justify-start',
        ]"
      >
        <img
          v-if="msg.sender === 'other'"
          :src="msg.avatar"
          alt="Avatar"
          class="mr-2 h-8 w-8 self-start rounded-full bg-gray-300 object-cover"
        />
        <div
          :class="[
            'max-w-[75%]',
            msg.sender === 'user' ?
              'rounded-br-none bg-blue-100 text-sm text-gray-800'
            : 'rounded-tl-none bg-white text-sm text-gray-800',
            'rounded-xl p-3 shadow-sm',
          ]"
        >
          <p v-html="msg.content"></p>
          <div
            v-for="attachment in msg.attachments"
            :key="attachment.id"
            :class="[
              'rounded-lg border p-1 shadow-sm',
              msg.sender === 'user' ?
                'border-blue-100 bg-blue-200'
              : 'border-white/30 bg-white/20',
            ]"
          >
            <a :href="attachment.url" download class="flex items-center">
              <component
                :is="getIconByFileName(attachment.name)"
                class="h-6 w-6 translate-y-1"
                :class="getColorByFileName(attachment.name)"
              />
              <p
                :class="[
                  'text-sm',
                  msg.sender === 'user' ? 'text-white-800' : 'text-gray-800',
                ]"
              >
                {{ attachment.name }} ({{ formatFileSize(attachment.size) }})
              </p>
            </a>
          </div>
          <div
            :class="[
              'text-sm text-[0.8rem]',
              msg.sender === 'user' ?
                'text-right text-blue-400'
              : 'text-right text-gray-500',
            ]"
          >
            {{
              new Date(msg.timestamp).toLocaleTimeString(undefined, {
                hour: '2-digit',
                minute: '2-digit',
              })
            }}
            <span v-if="msg.sender === 'user' && msg.isRead">✓ Read</span>
          </div>
        </div>

        <img
          v-if="msg.sender === 'user'"
          :src="msg.avatar"
          alt="Avatar"
          class="ml-2 h-8 w-8 self-start rounded-full bg-blue-300 object-cover"
        />
      </div>
    </div>
  </div>
</template>
