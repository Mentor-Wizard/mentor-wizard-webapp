import { computed, nextTick, ref } from 'vue';

const scrollContainer = ref(null);
const listUsers = ref([]);
const messageSortList = ['Resent', 'New', 'Name'];
const messageSortBy = ref(1);
const currentCompanion = ref(null);
const chatMessages = ref([]);
let channel = null;

export function useCaseChat() {
  const fetchUsers = async (user_id) => {
    const { data } = await axios.get(route('chat.users'));
    listUsers.value = data.users;
    if (listUsers.value.length > 0) await fetchMessages(listUsers.value[0].id);
    subscribeUser(user_id);
  };

  const fetchMessages = async (id) => {
    currentCompanion.value = listUsers.value.find((user) => user.id === id);
    const { data } = await axios.get(route('chat.messages', { receiver: id }));
    chatMessages.value = data.messages;
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
    const { data } = await axios.post(
      route('chat.send-messages', { receiver: currentCompanion.value.id }),
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    );
    chatMessages.value.push(data.message);
    await scrollToBottom();
  };

  const getMessages = async (id) => {
    const { data } = await axios.get(
      route('chat.get-messages', { message: id }),
    );
    chatMessages.value.push(data.message);
    await scrollToBottom();
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
    console.log('subscribeUser');

    const updateOnlineStatus = (userId, isOnline) => {
      const userIndex = listUsers.value.findIndex((u) => u.id === userId);

      if (userIndex !== -1) {
        listUsers.value[userIndex].online = isOnline;
      }
    };

    Echo.private(`Chat.${user_id}`).listen('Chats\\ChatMessageEvent', (e) => {
      const chatMessage = e.chatMessage;
      const index = listUsers.value.findIndex(
        (user) => user.id === chatMessage.sender_id,
      );
      if (index !== -1) {
        listUsers.value[index].last = 'now';
        listUsers.value[index].message = chatMessage.message;
        if (currentCompanion.value.id === chatMessage.sender_id) {
          getMessages(chatMessage.id);
        }
      }
    });

    channel = Echo.join('presence-online-users')
      .here((onlineUsersList) => {
        console.log('here. list users:', onlineUsersList);

        const onlineIds = new Set(onlineUsersList.map((u) => u.id));

        listUsers.value.forEach((user) => {
          console.log('onlineIds', onlineIds.has(user.id));
          user.online = onlineIds.has(user.id);
        });
      })
      .joining((user) => {
        console.log('joining. user:', user);
        updateOnlineStatus(user.id, true);
      })
      .leaving((user) => {
        console.log('leaving. Користувач відключився:', user);
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
    const users = [...listUsers.value];

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
    currentCompanion,
    subscribeUser,
    unsubscribeUser,
    fetchUsers,
    fetchMessages,
    sortedUsers,
    messageSortList,
    messageSortBy,
    chatMessages,
    sendMessage,
  };
}
