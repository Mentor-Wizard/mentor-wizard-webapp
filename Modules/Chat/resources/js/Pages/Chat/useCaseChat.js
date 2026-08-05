/* global Echo */
import axios from 'axios';
import { computed, nextTick, ref } from 'vue';

const scrollContainer = ref(null);
const listUser = ref([]);
const messageSortList = ['Resent', 'New', 'Name'];
const messageSortBy = ref(1);
const currentUser = ref(null);
const chatMessages = ref([]);
const chatFiles = ref([]);
const alertRef = ref(null);
const showArchiveModal = ref(false);
let channel = null;

export function useCaseChat() {
  const fetchUsers = async (user_id) => {
    const { data } = await axios.get(route('chat.users'));
    listUser.value = data.users;
    if (sortedUsers.value.length > 0)
      await fetchMessages(sortedUsers.value[0].chatId);
    subscribeUser(user_id);
  };

  const fetchMessages = async (chatId) => {
    currentUser.value = listUser.value.find((user) => user.chatId === chatId);
    const { data } = await axios.get(route('chat.messages', { chat: chatId }));
    chatMessages.value = data.messages;
    chatFiles.value = data.files;
    await scrollToBottom();
  };

  const sendMessage = async (files, message) => {
    const formData = new FormData();
    formData.append('message', message);
    if (files.value.length > 0) {
      files.value.forEach((file, index) => {
        formData.append(`files[${index}]`, file);
      });
    }
    try {
      const { data } = await axios.post(
        route('chat.send-message', { chat: currentUser.value.chatId }),
        formData,
        { headers: { 'Content-Type': 'multipart/form-data' } },
      );
      chatMessages.value.push(data.message);
      chatFiles.value = [
        ...(chatFiles.value ?? []),
        ...(data.message.attachments ?? []),
      ];
      await scrollToBottom();
    } catch (error) {
      currentUser.value.canSend = false;
      console.log(error);
      if (alertRef.value) {
        alertRef.value.open({
          title: 'Error',
          message: 'You cannot send messages to this user.',
          error: true,
          timeout: 5000,
        });
      }
    }
  };

  const getMessages = async (id) => {
    const { data } = await axios.get(
      route('chat.get-message', { message: id }),
    );
    chatMessages.value.push(data.message);
    chatFiles.value = [
      ...(chatFiles.value ?? []),
      ...(data.message.attachments ?? []),
    ];
    await scrollToBottom();
  };

  const setMute = async () => {
    const { data } = await axios.post(
      route('chat.set-mute', { chat: currentUser.value.chatId }),
      {
        isMuted: currentUser.value.isMuted ? 1 : 0,
      },
    );
    currentUser.value.isMuted = data.isMuted;
  };

  const setArchive = async () => {
    await axios.post(
      route('chat.set-archive', { chat: currentUser.value.chatId }),
    );
    showArchiveModal.value = false;
    const index = listUser.value.findIndex(
      (user) => user.id === currentUser.value.id,
    );
    if (index !== -1) {
      listUser.value.splice(index, 1);
      if (sortedUsers.value.length > 0)
        await fetchMessages(sortedUsers.value[0].id);
    }
  };
  const setBan = async () => {
    await axios.post(
      route('chat.set-ban', { chat: currentUser.value.chatId }),
      {
        ban: currentUser.value.ban ? 0 : 1,
      },
    );
    currentUser.value.ban = !currentUser.value.ban;
  };

  const scrollToBottom = async () => {
    await nextTick();
    if (scrollContainer.value) {
      scrollContainer.value.scrollTo({
        top: scrollContainer.value.scrollHeight,
        behavior: 'smooth',
      });
    }
  };

  const subscribeUser = (user_id) => {
    const updateOnlineStatus = (userId, isOnline) => {
      const userIndex = listUser.value.findIndex((u) => u.id === userId);
      if (userIndex !== -1) {
        listUser.value[userIndex].online = isOnline;
      }
    };

    Echo.private(`Chat.${user_id}`).listen('Chats\\ChatMessageEvent', (e) => {
      const message = e.message;
      const index = listUser.value.findIndex(
        (user) => user.id === message.user_id,
      );
      if (index !== -1) {
        listUser.value[index].last = 'now';
        listUser.value[index].message = message.message;
        if (currentUser.value.id === message.user_id) {
          getMessages(message.id);
        }
      }
    });

    channel = Echo.join('presence-online-users')
      .here((onlineUsersList) => {
        const onlineIds = new Set(onlineUsersList.map((u) => u.id));
        listUser.value.forEach((user) => {
          user.online = onlineIds.has(user.id);
        });
      })
      .joining((user) => {
        updateOnlineStatus(user.id, true);
      })
      .leaving((user) => {
        updateOnlineStatus(user.id, false);
      })
      .error((error) => {
        console.error('Error Presence Channel:', error);
      });
  };

  const unsubscribeUser = () => {
    if (channel) {
      channel.leave();
    }
  };

  const sortedUsers = computed(() => {
    const users = [...listUser.value];

    const sortIndex = messageSortBy.value;

    switch (sortIndex) {
      case 0: // 'Resent'
        return users.sort((a, b) => {
          // Використовуємо .getTime() для порівняння об'єктів Date
          const dateA = new Date(a['createdAt']).getTime();
          const dateB = new Date(b['createdAt']).getTime();
          // Сортування від більшого до меншого (новіші перші)
          return dateB - dateA;
        });

      case 1: // 'New'
        return users.sort((a, b) => {
          const dateA = new Date(a['createdAt']).getTime();
          const dateB = new Date(b['createdAt']).getTime();
          // Сортування від меншого до більшого (старіші перші)
          return dateB - dateA;
        });

      case 2: // 'Name'
        return users.sort((a, b) => {
          const nameA = a.name.toLowerCase();
          const nameB = b.name.toLowerCase();

          if (nameA < nameB) return -1;
          if (nameA > nameB) return 1;
          return 0;
        });

      default:
        return users;
    }
  });

  return {
    scrollContainer,
    listUser,
    currentUser,
    subscribeUser,
    unsubscribeUser,
    fetchUsers,
    fetchMessages,
    sortedUsers,
    messageSortList,
    messageSortBy,
    chatMessages,
    chatFiles,
    sendMessage,
    setMute,
    setArchive,
    setBan,
    alertRef,
    showArchiveModal,
  };
}
