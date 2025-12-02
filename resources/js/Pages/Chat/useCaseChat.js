import { ref } from 'vue';

const onlineUsers = ref([]);
let channel = null;
export function useCaseChat() {
  const subscribeUser = () => {
    console.log('subscribeUser');
    channel = Echo.join('presence-online-users')
      .here((users) => {
        onlineUsers.value = users;
        console.log('here');
        console.log(users);
      })
      .joining((user) => {
        if (!onlineUsers.value.find((u) => u.id === user.id)) {
          onlineUsers.value.push(user);
        }
      })
      .leaving((user) => {
        console.log('leaving');
        console.log(user);
      });
  };

  const unsubscribeUser = () => {
    if (channel) {
      channel.leave(); // коректне відписування при переході компоненту
    }
  };

  return { subscribeUser, unsubscribeUser };
}
