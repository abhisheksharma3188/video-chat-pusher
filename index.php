<?php
    $user_timestamp=time();
    $user_id=uniqid();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
    body {
      background: #0098ff;
      display: flex;
      height: 100vh;
      margin: 0;
      align-items: center;
      justify-content: center;
      padding: 0 50px;
      font-family: -apple-system, BlinkMacSystemFont, sans-serif;
    }
    video {
      max-width: calc(50% - 100px);
      margin: 0 50px;
      box-sizing: border-box;
      border-radius: 2px;
      padding: 0;
      background: white;
    }
    .copy {
      position: fixed;
      top: 10px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 16px;
      color: white;
    }
  </style>
</head>
<body>
    <div class="copy">Send your URL to a friend to start a video call</div>
    <video id="localVideo" autoplay muted></video>
    <video id="remoteVideo" autoplay></video>
    
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <script>
        var user_id="<?php echo $user_id; ?>";
        var user_timestamp="<?php echo $user_timestamp; ?>";
    
        var MY_CHANNEL="MY_CHANNEL";
        var APP_KEY = "APP_KEY";
        var APP_CLUSTER = "APP_CLUSTER";

        var message;
        var isOfferer;

        var users_array=[];

        ///////////////// code to initialize pusher below ///////////////
        var pusher = new Pusher(APP_KEY, {
            cluster: APP_CLUSTER,
        });
        ///////////////// code to initialize pusher above ///////////////

        ///////////////// code to subscribe channel below ///////////////
        var channel = pusher.subscribe(MY_CHANNEL);
        ///////////////// code to subscribe channel above ///////////////

        /// code to check if channel is subscribed successfully and send user info below ///
        channel.bind('pusher:subscription_succeeded', () => {
            message=`{"attendance":"true"}`;
            send_message(message); 
        });
        /// code to check if channel is subscribed successfully and send user info above ///

        ///////////////// code to receive messages below ////////////////
        channel.bind("my-event", (data) => {
            //console.log(data);
            message=JSON.parse(data);
            console.log(message);
            if(message.hasOwnProperty('attendance')){
                users_array=[];
                message=`{"user_id":"${user_id}","user_timestamp":${user_timestamp}}`;
                send_message(message); 
            }
            if(message.hasOwnProperty('user_id')){
                if(users_array.findIndex(obj => obj.user_id === message.user_id)==-1){
                    users_array.push(message);
                }
                if(users_array.length>2){
                    userObjectWithMaxTimestamp = users_array.reduce((maxObj, obj) => (obj.user_timestamp > maxObj.user_timestamp ? obj : maxObj), users_array[0]);
                    console.log(userObjectWithMaxTimestamp);

                    message=`{"disconnect_user_id":"${userObjectWithMaxTimestamp.user_id}"}`;
                    send_message(message);
                    users_array = users_array.filter(obj => obj.user_id !== userObjectWithMaxTimestamp.user_id);
                }
                console.log(users_array);
                if(users_array.length==2){
                    userObjectWithMaxTimestamp = users_array.reduce((maxObj, obj) => (obj.user_timestamp > maxObj.user_timestamp ? obj : maxObj), users_array[0]);
                    if(user_id==userObjectWithMaxTimestamp.user_id){
                        isOfferer=true;
                    }else{
                        isOfferer=false;
                    }
                    console.log(isOfferer);
                    startWebRTC(isOfferer);
                }
            }
            if(message.hasOwnProperty('disconnect_user_id')){
                if(user_id==message.disconnect_user_id){
                    window.location.href="https://google.co.in";
                }
            }
            if(message.hasOwnProperty('sender_id')){
                if(message.sender_id==user_id){
                    return;
                }
                if (message.sdp) {
                // This is called after receiving an offer or answer from another peer
                pc.setRemoteDescription(new RTCSessionDescription(message.sdp), () => {
                    // When receiving an offer lets answer it
                    if (pc.remoteDescription.type === 'offer') {
                    pc.createAnswer().then(localDescCreated).catch(onError);
                    }
                }, onError);
                } else if (message.candidate) {
                // Add the new ICE candidate to our connections remote description
                pc.addIceCandidate(
                    new RTCIceCandidate(message.candidate), onSuccess, onError
                );
                }
            }
            
        });
        ///////////////// code to receive messages above ////////////////

        ///////////////// function to send messages below ///////////////////
        function send_message(message){
            data={"message":message};
            var headers_obj = {"Authorization":"Bearer <?php echo '1234567890'; /*@$_COOKIE['jwt_token_website'];*/ ?>"};
            $.ajax({
                url: "pusher.php",
                type: "POST",
                data: data,
                headers:headers_obj,
                success: function(data) {
                    //console.log(data);
                    //console.log('ajax complete');
                    
                },
                error: function() {
                    alert('Some Error Occured.');
                }
            });
        }
        ///////////////// function to send messages above ///////////////////

        

    </script>

    <script>
        const configuration = {
            iceServers: [{
                urls: 'stun:stun.l.google.com:19302'
            }]
        };
        function onSuccess() {};
        function onError(error) {
            console.log(error);
        };

        function startWebRTC(isOfferer) {
          pc = new RTCPeerConnection(configuration);
          
          // 'onicecandidate' notifies us whenever an ICE agent needs to deliver a
          // message to the other peer through the signaling server
          pc.onicecandidate = event => {
            if (event.candidate) {
              send_message(JSON.stringify({'sender_id':user_id,'candidate': event.candidate}));
            }
          };
          
          // If user is offerer let the 'negotiationneeded' event create the offer
          if (isOfferer) {
            pc.onnegotiationneeded = () => {
              pc.createOffer().then(localDescCreated).catch(onError);
            }
          }
          
          // When a remote stream arrives display it in the #remoteVideo element
          pc.onaddstream = event => {
              /*if(isOfferer==false){
                  alert("you got a call");
              }*/
            remoteVideo.srcObject = event.stream;
          };
          
          navigator.mediaDevices.getUserMedia({
            audio: true,
            video: true,
          }).then(stream => {
            // Display your local video in #localVideo element
            localVideo.srcObject = stream;
            // Add your stream to be sent to the conneting peer
            pc.addStream(stream);
          }, onError);
          
        }

        function localDescCreated(desc) {
          pc.setLocalDescription(
            desc,
            () => send_message(JSON.stringify({'sender_id':user_id,'sdp': pc.localDescription})),
            onError
          );
        }
    </script>   

</body>
</html>
