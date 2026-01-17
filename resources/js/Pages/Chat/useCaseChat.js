import { computed, nextTick, ref } from 'vue';

const scrollContainer = ref(null);
const listChat = ref([]);
const messageSortList = ['Resent', 'New', 'Name'];
const messageSortBy = ref(1);
const currentChat = ref(null);
const chatMessages = ref([]);
const chatFiles = ref([]);
let channel = null;

export function useCaseChat() {
  const fetchUsers = async (user_id) => {
    const { data } = await axios.get(route('chat.users'));
    listChat.value = data.users;
    if (sortedUsers.value.length > 0)
      await fetchMessages(sortedUsers.value[0].id);
    subscribeUser(user_id);
  };

  const fetchMessages = async (id) => {
    currentChat.value = listChat.value.find((user) => user.id === id);
    const { data } = await axios.get(route('chat.messages', { chat: id }));
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
        route('chat.send-message', { chat: currentChat.value.id }),
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
      currentChat.value.canSend = false;
      alert('You cannot send messages to this user.');
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
    await axios.post(route('chat.set-mute', { chat: currentChat.value.id }), {
      mute: currentChat.value.mute ? 1 : 0,
    });
  };

  const setArchive = async () => {
    if (window.confirm('Are you sure you want to delete the chat?')) {
      await axios.post(
        route('chat.set-archive', { chat: currentChat.value.id }),
      );
      const index = listChat.value.findIndex(
        (user) => user.id === currentChat.value.id,
      );
      if (index !== -1) {
        listChat.value.splice(index, 1);
        if (sortedUsers.value.length > 0)
          await fetchMessages(sortedUsers.value[0].id);
      }
    }
  };
  const setBan = async () => {
    await axios.post(route('chat.set-ban', { chat: currentChat.value.id }), {
      ban: currentChat.value.ban ? 0 : 1,
    });
    currentChat.value.ban = !currentChat.value.ban;
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
      const userIndex = listChat.value.findIndex(
        (u) => u.companion_id === userId,
      );
      if (userIndex !== -1) {
        listChat.value[userIndex].online = isOnline;
      }
    };

    Echo.private(`Chat.${user_id}`).listen('Chats\\ChatMessageEvent', (e) => {
      const chat = e.chat;
      const message = e.message;
      const index = listChat.value.findIndex((chat) => chat.id === chat.id);
      if (index !== -1) {
        listChat.value[index].last = 'now';
        listChat.value[index].message = message.message;
        if (currentChat.value.id === chat.id) {
          getMessages(message.id);
        }
      }
    });

    channel = Echo.join('presence-online-users')
      .here((onlineUsersList) => {
        const onlineIds = new Set(onlineUsersList.map((u) => u.id));
        listChat.value.forEach((chat) => {
          chat.online = onlineIds.has(chat.companion_id);
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
    const users = [...listChat.value];

    const sortIndex = messageSortBy.value;

    switch (sortIndex) {
      case 0: // 'Resent'
        return users.sort((a, b) => {
          // Використовуємо .getTime() для порівняння об'єктів Date
          const dateA = new Date(a['created_at']).getTime();
          const dateB = new Date(b['created_at']).getTime();
          // Сортування від більшого до меншого (новіші перші)
          return dateB - dateA;
        });

      case 1: // 'New'
        return users.sort((a, b) => {
          const dateA = new Date(a['created_at']).getTime();
          const dateB = new Date(b['created_at']).getTime();
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
    currentChat,
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
  };
}
