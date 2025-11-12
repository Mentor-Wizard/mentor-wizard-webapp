<script setup>
import { computed, ref } from 'vue';

import { useCaseFileType } from '../useCaseFileType.js';

const { getColorByFileName, getIconByFileName, formatFileSize } =
  useCaseFileType();

// TODO - This is a simulation of our Message data structure. After connecting to the backend, you need to delete
const mockMessage = (id, sender, timestamp, content, attachments = []) => ({
  id,
  sender,
  senderAvatar:
    sender === 'user' ?
      'https://img.freepik.com/free-photo/portrait-white-man-isolated_53876-40306.jpg'
    : 'https://img.freepik.com/free-photo/beautiful-blonde-woman-portrait-smiling-face_53876-137593.jpg',
  timestamp,
  content,
  isRead: true,
  attachments,
});

// TODO - this function generates content to display the chat. After connecting to the backend, you need to delete
const generateRandomData = () => {
  const days = 5;
  const rawMessages = [];
  let messageId = 1;

  for (let d = 0; d < days; d++) {
    const day = new Date();

    day.setDate(day.getDate() - d);

    const messagesPerDay = Math.floor(Math.random() * 5) + 3;

    for (let i = 0; i < messagesPerDay; i++) {
      const sender = Math.random() > 0.5 ? 'user' : 'other';
      const hours = Math.floor(Math.random() * 24);
      const minutes = Math.floor(Math.random() * 60);

      const messageTime = new Date(day);
      messageTime.setHours(hours, minutes, 0, 0);

      let content = `Це повідомлення №${messageId} від ${sender}.`;
      if (Math.random() < 0.4) {
        content = 'Ось прикріплені матеріали для перегляду.';
        rawMessages.push(
          mockMessage(messageId++, sender, messageTime.toISOString(), content, [
            {
              id: 1,
              name: `Pdf-${d}-${i}.pdf`,
              size: 128,
              url: 'http://asdfadsfadf/asdfasdf.pgp',
            },
            {
              id: 2,
              name: `Vue-${d}-${i}.js`,
              size: 12805,
              url: 'http://asdfadsfadf/asdfasdf.pgp',
            },
            {
              id: 3,
              name: `Doc-${d}-${i}.doc`,
              size: 125218,
              url: 'http://asdfadsfadf/asdfasdf.pgp',
            },
          ]),
        );
      } else {
        rawMessages.push(
          mockMessage(messageId++, sender, messageTime.toISOString(), content),
        );
      }
    }
  }

  return rawMessages.sort(
    (a, b) => new Date(a.timestamp) - new Date(b.timestamp),
  );
};

const rawMessages = ref(generateRandomData());

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

  for (const message of rawMessages.value) {
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
          :src="msg.senderAvatar"
          alt="Avatar"
          class="mr-2 h-8 w-8 self-start rounded-full bg-gray-300 object-cover"
        />
        <div
          :class="[
            'max-w-[75%]',
            msg.sender === 'user' ?
              'rounded-br-none bg-blue-600 text-sm text-white'
            : 'rounded-tl-none bg-white text-sm text-gray-800',
            'rounded-xl p-3 shadow-sm',
          ]"
        >
          <p>{{ msg.content }}</p>
          <div
            v-for="attachment in msg.attachments"
            :key="attachment.id"
            :class="[
              'rounded-lg border p-1 shadow-sm',
              msg.sender === 'user' ?
                'border-blue-500 bg-blue-600'
              : 'border-white/30 bg-white/20',
            ]"
          >
            <a :href="attachment.url" class="flex items-center">
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
                'text-right text-blue-200'
              : 'text-right text-gray-500',
            ]"
          >
            {{
              new Date(msg.timestamp).toLocaleTimeString(undefined, {
                hour: '2-digit',
                minute: '2-digit',
              })
            }}
            <span v-if="msg.isRead">✓ Read</span>
          </div>
        </div>

        <img
          v-if="msg.sender === 'user'"
          :src="msg.senderAvatar"
          alt="Avatar"
          class="ml-2 h-8 w-8 self-start rounded-full bg-blue-300 object-cover"
        />
      </div>
    </div>
  </div>
</template>
